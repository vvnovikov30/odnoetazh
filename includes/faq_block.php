<?php
/**
 * includes/faq_block.php — семантический FAQ статьи + JSON-LD FAQPage.
 *
 * Компонент подключается из templates/template-article.php перед footer.php.
 * Данные читаются из data/faq_registry.json по слагу текущей статьи:
 *   articles[slug] = { faq_title, faq_lead, items[{id, question, answer, norm}] }
 *
 * Разметка доступна без JavaScript: <details>/<summary>. JSON-LD печатается
 * один раз на страницу и повторяет текст вопросов и ответов без расхождений.
 */

if (!function_exists('faq_block_esc')) {
    /**
     * Экранирование текста для HTML-контекста.
     */
    function faq_block_esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('faq_block_slug')) {
    /**
     * Слаг текущей статьи: переменная SSG $slug либо имя PHP-файла.
     */
    function faq_block_slug(): string
    {
        $slug = $GLOBALS['slug'] ?? '';
        if (is_string($slug) && $slug !== '') {
            return $slug;
        }

        $script = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
        $base = basename(str_replace('\\', '/', (string) $script));
        $slug = preg_replace('/\.php$/i', '', $base);

        return is_string($slug) ? $slug : '';
    }
}

$faqSlug = faq_block_slug();
$faqRegistryPath = dirname(__DIR__) . '/data/faq_registry.json';

if ($faqSlug === '' || !is_readable($faqRegistryPath)) {
    return;
}

$faqRegistryRaw = file_get_contents($faqRegistryPath);
if ($faqRegistryRaw === false) {
    return;
}

$faqRegistry = json_decode($faqRegistryRaw, true);
if (!is_array($faqRegistry) || !isset($faqRegistry['articles'][$faqSlug])) {
    return;
}

$faqArticle = $faqRegistry['articles'][$faqSlug];
$faqItems = [];
$faqUsedIds = [];

foreach (($faqArticle['items'] ?? []) as $faqItem) {
    if (!is_array($faqItem)) {
        continue;
    }

    $faqQuestion = trim((string) ($faqItem['question'] ?? ''));
    $faqAnswer = trim((string) ($faqItem['answer'] ?? ''));
    if ($faqQuestion === '' || $faqAnswer === '') {
        continue;
    }

    $faqItemId = trim((string) ($faqItem['id'] ?? ''));
    if ($faqItemId === '' || isset($faqUsedIds[$faqItemId])) {
        $faqItemId = 'faq-item-' . (count($faqItems) + 1);
    }
    $faqUsedIds[$faqItemId] = true;

    $faqItems[] = [
        'id' => $faqItemId,
        'question' => $faqQuestion,
        'answer' => $faqAnswer,
        'norm' => trim((string) ($faqItem['norm'] ?? '')),
    ];
}

if ($faqItems === []) {
    return;
}

$faqSite = rtrim((string) ($faqRegistry['site'] ?? 'https://xn--80ahhialrcec8b1i.xn--p1ai'), '/');
$faqPageUrl = $faqSite . '/blog/' . rawurlencode($faqSlug) . '.php';
$faqUrl = $faqPageUrl . '#faq';
$faqTitle = trim((string) ($faqArticle['faq_title'] ?? 'Инженерный FAQ'));
$faqLead = trim((string) ($faqArticle['faq_lead'] ?? ''));
?>
<section class="faq-section" id="faq" aria-labelledby="faq-title">
    <div class="container">
        <p class="faq-section__eyebrow">AIO · инженерные ответы</p>
        <h2 class="faq-section__title" id="faq-title"><?php echo faq_block_esc($faqTitle); ?></h2>
        <?php if ($faqLead !== ''): ?>
            <p class="faq-section__lead"><?php echo faq_block_esc($faqLead); ?></p>
        <?php endif; ?>

        <div class="faq-section__list">
            <?php foreach ($faqItems as $index => $faqItem): ?>
                <?php $faqItemId = $faqItem['id'] !== '' ? $faqItem['id'] : 'faq-item-' . ($index + 1); ?>
                <details class="faq-item" id="<?php echo faq_block_esc($faqItemId); ?>"<?php echo $index === 0 ? ' open' : ''; ?>>
                    <summary class="faq-question">
                        <span class="faq-question__text"><?php echo faq_block_esc($faqItem['question']); ?></span>
                        <span class="faq-question__marker" aria-hidden="true"></span>
                    </summary>
                    <div class="faq-answer">
                        <p><?php echo faq_block_esc($faqItem['answer']); ?></p>
                        <?php if ($faqItem['norm'] !== ''): ?>
                            <p class="faq-answer__norm">
                                <span>Норматив:</span>
                                <?php echo faq_block_esc($faqItem['norm']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
$faqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    '@id' => $faqUrl,
    'url' => $faqUrl,
    'inLanguage' => 'ru-RU',
    'mainEntity' => [],
];

foreach ($faqItems as $faqItem) {
    $faqItemId = $faqItem['id'] !== '' ? $faqItem['id'] : 'faq-item';
    $faqSchema['mainEntity'][] = [
        '@type' => 'Question',
        '@id' => $faqPageUrl . '#' . $faqItemId,
        'name' => $faqItem['question'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $faqItem['answer'],
        ],
    ];
}
?>
<script type="application/ld+json">
<?php echo json_encode(
    $faqSchema,
    JSON_UNESCAPED_UNICODE
    | JSON_UNESCAPED_SLASHES
    | JSON_HEX_TAG
    | JSON_HEX_AMP
    | JSON_HEX_APOS
    | JSON_HEX_QUOT
    | JSON_PRETTY_PRINT
); ?>
</script>
