<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Request;

/**
 * DocumentationController
 *
 * Renders Markdown documentation pages (GUIDE.md and WHMCS.md) as styled
 * admin views with syntax styling and navigation tabs.
 */
class DocumentationController extends Controller
{
    public function show(Request $request): void
    {
        $doc = strtolower(trim((string) $request->query('doc', $request->query('page', 'guide'))));
        if (!in_array($doc, ['guide', 'whmcs'], true)) {
            $doc = 'guide';
        }

        $fileName = $doc === 'whmcs' ? 'WHMCS.md' : 'GUIDE.md';
        $fileTitle = $doc === 'whmcs' ? 'WHMCS License Integration Guide' : 'ELMS API & Architecture Guide';

        $file = ELMS_ROOT . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . $fileName;
        $html = is_file($file)
            ? $this->renderMarkdown((string) file_get_contents($file))
            : '<div class="alert alert-warning">Documentation file (docs/' . htmlspecialchars($fileName) . ') not found.</div>';

        $this->view('docs/index', [
            'title'       => $fileTitle,
            'current_doc' => $doc,
            'doc_html'    => $html,
            'flash'       => self::pullFlash(),
        ]);
    }

    /**
     * Convert a subset of Markdown to safe, beautiful HTML.
     */
    private function renderMarkdown(string $md): string
    {
        $lines = explode("\n", $md);
        $n = count($lines);
        $html = '';
        $inCode = false;
        $codeBuf = [];
        $codeLang = '';
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

            // Fenced code block
            if (preg_match('/^```([a-zA-Z0-9_-]*)/', trim($line), $codeMatch)) {
                if ($inCode) {
                    $escaped = htmlspecialchars(implode("\n", $codeBuf), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $html .= '<div class="position-relative my-3 code-block-wrapper">';
                    if ($codeLang !== '') {
                        $html .= '<span class="badge bg-secondary position-absolute top-0 end-0 m-2 font-monospace text-uppercase" style="font-size: 10px; z-index: 2;">' . htmlspecialchars($codeLang) . '</span>';
                    }
                    $html .= '<pre class="elms-code m-0"><code class="language-' . htmlspecialchars($codeLang ?: 'text') . '">' . $escaped . '</code></pre></div>';
                    $codeBuf = [];
                    $codeLang = '';
                    $inCode = false;
                } else {
                    $flushList();
                    $inCode = true;
                    $codeLang = $codeMatch[1] ?? '';
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

            // Headers
            if (preg_match('/^(#{1,4})\s+(.*)$/', $line, $m)) {
                $flushList();
                $lvl = strlen($m[1]);
                $html .= "<h{$lvl} class=\"mt-4 mb-2\">" . $this->inline($m[2]) . "</h{$lvl}>";
                $i++;
                continue;
            }

            // Horizontal rule
            if (trim($line) === '---' || trim($line) === '***') {
                $flushList();
                $html .= '<hr class="my-4">';
                $i++;
                continue;
            }

            // Blockquote
            if (preg_match('/^>\s?(.*)$/', $line, $m)) {
                $flushList();
                $html .= '<blockquote class="border-start border-3 border-primary ps-3 my-3 text-muted">' . $this->inline($m[1]) . '</blockquote>';
                $i++;
                continue;
            }

            // Table
            if (str_contains($line, '|')
                && $i + 1 < $n
                && str_contains($lines[$i + 1], '-')
                && preg_match('/^\s*\|?[\s:|-]+\|?\s*$/', $lines[$i + 1])
            ) {
                $flushList();
                $html .= $this->renderTable($line, $lines[$i + 1], $lines, $i);
                continue;
            }

            // Unordered list
            if (preg_match('/^\s*[-*]\s+(.*)$/', $line, $m)) {
                if ($listType !== 'ul') {
                    $flushList();
                    $listType = 'ul';
                }
                $listBuf[] = '<li>' . $this->inline($m[1]) . '</li>';
                $i++;
                continue;
            }

            // Ordered list
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
            $html .= '<p class="mb-3">' . $this->inline($line) . '</p>';
            $i++;
        }

        if ($inCode) {
            $escaped = htmlspecialchars(implode("\n", $codeBuf), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $html .= '<div class="position-relative my-3 code-block-wrapper"><pre class="elms-code m-0"><code>' . $escaped . '</code></pre></div>';
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
        return '<div class="table-responsive my-3"><table class="table table-bordered table-sm elms-table align-middle">'
            . "<thead class=\"table-light\">{$head}</thead><tbody>{$rows}</tbody></table></div>";
    }

    private function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = preg_replace_callback('/`([^`]+)`/', static fn ($m) => '<code class="font-monospace text-primary">' . $m[1] . '</code>', $text);
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', static function ($m) {
            $url = $m[2];
            if (!preg_match('#^(https?://|mailto:|/|#)#', $url)) {
                $url = '#';
            }
            return '<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" target="_blank" rel="noopener" class="text-decoration-underline">' . $m[1] . '</a>';
        }, $text);
        return $text;
    }
}
