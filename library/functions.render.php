<?php if (!defined('APPLICATION'))
    exit();

if (!function_exists('ArticleUrl')) {
    function ArticleUrl($Article, $Page = '', $WithDomain = true) {
        // If $Article type is an array, then cast it to an object.
        if (is_array($Article))
            $Article = (object)$Article;

        // Set up the initial URL string.
        $Result = '/article/' . Gdn_Format::Date($Article->DateInserted, '%Y') . '/' . $Article->UrlCode;

        // Add in the page number if necessary.
        if ($Page && ($Page > 1 || Gdn::Session()->UserID))
            $Result .= '/p' . $Page;

        // Add a trailing slash.
        $Result .= '/';

        return Url($Result, $WithDomain);
    }
}

if (!function_exists('FormatArticleBody')) {
    function FormatArticleBody($ArticleBody, $Format = 'Html') {
        if (strcasecmp($Format, 'Html') == 0) {
            // Format links and links to videos.
            $ArticleBody = Gdn_Format::Links($ArticleBody);

            // Mentions and hashes.
            $ArticleBody = Gdn_Format::Mentions($ArticleBody);

            // Format new lines.
            $ArticleBody = preg_replace("/(\015\012)|(\015)|(\012)/", "<br />", $ArticleBody);
            $ArticleBody = FixNl2Br($ArticleBody);

            // Convert br to paragraphs.
            $ArticleBody = preg_replace('#(?:<br\s*/?>\s*?){2,}#', '</p><p>', $ArticleBody);
            // Add p on first paragraph.
            $ArticleBody = '<p>' . $ArticleBody . '</p>';
        } else {
            $ArticleBody = Gdn_Format::To($ArticleBody, $Format);
        }

        return $ArticleBody;
    }
}

if (!function_exists('ArticleCategoryUrl')) {
    function ArticleCategoryUrl($Category, $Page = '', $WithDomain = true) {
        // If $Article type is an array, then cast it to an object.
        if (is_array($Category))
            $Category = (object)$Category;

        // Set up the initial URL string.
        $Result = '/articles/category/' . $Category->UrlCode;

        // Add in the page number if necessary.
        if ($Page && ($Page > 1 || Gdn::Session()->UserID))
            $Result .= '/p' . $Page;

        // Add a trailing slash.
        $Result .= '/';

        return Url($Result, $WithDomain);
    }
}
