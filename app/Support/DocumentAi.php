<?php

declare(strict_types=1);

namespace App\Support;

use Google\Cloud\DocumentAI\V1\Document;
use Google\Cloud\DocumentAI\V1\Document\TextAnchor;
use Google\Cloud\DocumentAI\V1\DocumentProcessorServiceClient;
use Google\Cloud\DocumentAI\V1\RawDocument;
use Google\Protobuf\Internal\RepeatedField;
use Illuminate\Support\Str;

class DocumentAi
{
    private string $project_id;

    private string $location;

    private string $processor_id;

    private Document $document;

    public function __construct()
    {
        $this->project_id = config('document.project_id');
        $this->processor_id = config('document.processor_id');
        $this->location = config('document.location');
    }

    public function getDocument(): Document
    {
        return $this->document;
    }

    /**
     * @throws \Google\ApiCore\ValidationException
     * @throws \Google\ApiCore\ApiException
     */
    public function process(string $content, string $mime_type = 'application/pdf'): static
    {
        $rawDocument = new RawDocument(compact('content', 'mime_type'));
        $client = new DocumentProcessorServiceClient([
            'credentials' => config('document.key_path'),
        ]);

        $processorName = $client->processorName($this->project_id, $this->location, $this->processor_id);
        $response =  $client->processDocument($processorName, compact('rawDocument'));

        $this->document = $response->getDocument();

        return $this;
    }

    public function extractTables(): array
    {
        if (! isset($this->document)) {
            throw new \Exception('Cannot extract table when document is not initialized.');
        }

        $pages = $this->document->getPages();
        $text = $this->document->getText();
        $all_tables = [];

        foreach ($pages as $page) {
            $tables = $page->getTables();

            foreach ($tables as $table) {
                $header_rows_values = collect($this->getTableData($table->getHeaderRows(), $text))->flatten();
                $body_rows_values = $this->getTableData($table->getBodyRows(), $text);
                $table_data = collect($body_rows_values)
                    ->map(function (array $row) use ($header_rows_values) {
                        return $header_rows_values->combine($row);
                    })
                    ->toArray();

                $all_tables[] = $table_data;
            }
        }

        return $all_tables;
    }

    protected function getTableData(RepeatedField $rows, string $text): array
    {
        $values = [];

        foreach ($rows as $row) {
            $current_values = [];
            $cells = $row->getCells();

            foreach ($cells as $cell) {
                $current_values[] = $this->textAnchorToText($cell->getLayout()->getTextAnchor(), $text);
            }

            $values[] = $current_values;
        }

        return $values;
    }

    protected function textAnchorToText(TextAnchor $textAnchor, string $text): string
    {
        $response = Str::of('');
        $segments = $textAnchor->getTextSegments();

        foreach ($segments as $segment) {
            $start_index = (int) $segment->getStartIndex();
            $end_index = (int) $segment->getEndIndex();
            $length = $end_index - $start_index;

            $response = $response->append(Str::substr($text, $start_index, $length));
        }

        return $response->trim()->replace("\n", ' ')->toString();
    }
}
