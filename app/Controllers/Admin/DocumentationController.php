<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;

/**
 * DocumentationController
 *
 * Renders the project GUIDE.md (Markdown) as a styled admin page at
 * /admin/docs.
 */
class DocumentationController extends Controller
{
    public function show(Request $request): void
    {
        $file = ELMS_ROOT . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'GUIDE.md';
        $html = is_file($file)
            ? $this->renderMarkdown((string) file_get_contents($file))
            : '<div class="alert alert-warning">Documentation file (docs/GUIDE.md) not found.</div>';

        $this->view('docs/index', [
            'title'    => 'Documentation',
            'doc_html' => $html,
            'flash'    => self::pullFlash(),
        ]);
    }

    /**
     * Convert a subset of Markdown to safe HTML.
     */
    private function renderMarkdown(string $md): string
    {
        $lines = explode("\n", $md);
        $n = count($lines);
        $html = '';
        $inCode = false;
        $codeBuf = [];
        $listType = null;
        $listBuf = [];

        $flushList = function () use (&$html, &$listType, &$listBuf): void {
            if ($listType !== null) {
                $html .= "<{$listType}>" . implode('', $listBuf) . "</{$listType}>";
                $listType = null;
                $listBuf = [];
            }
        };

        $i = 0;
        while ($i < $n) {
            $line = $lines[$i];

            // Fenced code block.
            if (str_starts_with(trim($line), '```')) {
                if ($inCode) {
                    $html .= '<pre class="elms-code"><code>' . htmlspecialchars(implode("\n", $codeBuf), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>';
                    $codeBuf = [];
                    $inCode = false;
                } else {
                    $flushList();
                    $inCode = true;
                    $codeBuf = [];
                }
                $i++;
                continue;
            }
            if ($inCode) {
                $codeBuf[] = $line;
                $i++;
                continue;
            }

            if (trim($line) === '') {
                $flushList();
                $i++;
                continue;
            }

            if (preg_match('/^(#{1,4})\s+(.*)$/', $line, $m)) {
                $flushList();
                $lvl = strlen($m[1]);
                $html .= "<h{$lvl}>" . $this->inline($m[2]) . "</h{$lvl}>";
                $i++;
                continue;
            }

            if (trim($line) === '---') {
                $flushList();
                $html .= '<hr>';
                $i++;
                continue;
            }

            if (preg_match('/^>\s?(.*)$/', $line, $m)) {
                $flushList();
                $html .= '<blockquote>' . $this->inline($m[1]) . '</blockquote>';
                $i++;
                continue;
            }

            // Table: header row + separator row.
            if (str_contains($line, '|')
                && $i + 1 < $n
                && str_contains($lines[$i + 1], '-')
                && preg_match('/^\s*\|?[\s:|-]+\|?\s*$/', $lines[$i + 1])
            ) {
                $flushList();
                $html .= $this->renderTable($line, $lines[$i + 1], $lines, $i);
                continue;
            }

            if (preg_match('/^\s*[-*]\s+(.*)$/', $line, $m)) {
                if ($listType !== 'ul') {
                    $flushList();
                    $listType = 'ul';
                }
                $listBuf[] = '<li>' . $this->inline($m[1]) . '</li>';
                $i++;
                continue;
            }
            if (preg_match('/^\s*\d+\.\s+(.*)$/', $line, $m)) {
                if ($listType !== 'ol') {
                    $flushList();
                    $listType = 'ol';
                }
                $listBuf[] = '<li>' . $this->inline($m[1]) . '</li>';
                $i++;
                continue;
            }

            $flushList();
            $html .= '<p>' . $this->inline($line) . '</p>';
            $i++;
        }

        if ($inCode) {
            $html .= '<pre class="elms-code"><code>' . htmlspecialchars(implode("\n", $codeBuf), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>';
        }
        $flushList();

        return $html;
    }

    /**
     * @param array<int,string> $lines
     */
    private function renderTable(string $headerLine, string $sepLine, array $lines, int &$i): string
    {
        $i += 2;
        $cells = static function (string $row): array {
            $row = trim(trim($row), '|');
            return array_map('trim', explode('|', $row));
        };
        $headers = $cells($headerLine);
        $rows = '';
        while ($i < count($lines) && str_contains($lines[$i], '|') && trim($lines[$i]) !== '') {
            if (str_contains($lines[$i], '-') && preg_match('/^\s*\|?[\s:|-]+\|?\s*$/', $lines[$i])) {
                break;
            }
            $c = $cells($lines[$i]);
            $rows .= '<tr>' . implode('', array_map(fn ($x) => '<td>' . $this->inline($x) . '</td>', $c)) . '</tr>';
            $i++;
        }
        $head = '<tr>' . implode('', array_map(fn ($x) => '<th>' . $this->inline($x) . '</th>', $headers)) . '</tr>';
        return '<div class="table-responsive"><table class="table table-bordered elms-table">'
            . "<thead>{$head}</thead><tbody>{$rows}</tbody></table></div>";
    }

    private function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = preg_replace_callback('/`([^`]+)`/', static fn ($m) => '<code>' . $m[1] . '</code>', $text);
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', static function ($m) {
            $url = $m[2];
            if (!preg_match('#^(https?://|mailto:|/|#)#', $url)) {
                $url = '#';
            }
            return '<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" target="_blank" rel="noopener">' . $m[1] . '</a>';
        }, $text);
        return $text;
    }
}
