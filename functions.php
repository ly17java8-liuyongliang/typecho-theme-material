<?php

define("MATERIAL_VERSION", "3.2.1");

require_once("lib/UACheck.php");
require_once("lib/pangu.php");
require_once("lib/ThemeOptionRender.php");
require_once("lib/ThemeOption.php");

error_reporting(0);

/**
 * JavaScript LS 载入
 * @param string name
 * @param string uri
 */
function jsLsload($name, $uri)
{
    $options = Helper::options();
    $md5 = md5(file_get_contents($options->themeFile(getTheme(), $uri)));
    $base64 = base64_encode($md5);
    echo '<script>lsloader.load("' . $name . '","' . getThemeFile($uri) . '?' . $base64 . '", true)</script>';
}

/**
 * CSS LS 载入
 * @param string name
 * @param string uri
 */
function cssLsload($name, $uri)
{
    $options = Helper::options();
    $md5 = md5(file_get_contents($options->themeFile(getTheme(), $uri)));
    $base64 = base64_encode($md5);
    echo '<style id="' . $name . '"></style>';
    echo '<script>if(typeof window.lsLoadCSSMaxNums === "undefined")window.lsLoadCSSMaxNums = 0;window.lsLoadCSSMaxNums++;lsloader.load("' . $name . '","' . getThemeFile($uri) . '?' . $base64 . '",function(){if(typeof window.lsLoadCSSNums === "undefined")window.lsLoadCSSNums = 0;window.lsLoadCSSNums++;if(window.lsLoadCSSNums == window.lsLoadCSSMaxNums)document.documentElement.style.display="";}, false)</script>';
}

function getThemeFile($uri)
{
    $options = Helper::options();
    $themeOptions = getThemeOptions();
    if ($themeOptions["CDNType"] == 1) {
        return "https://cdn.jsdelivr.net/gh/idawnlight/typecho-theme-material@" . MATERIAL_VERSION . "/" . $uri;
    } elseif ($themeOptions["CDNType"] == 2) {
        return $themeOptions["CDNURL"] . "/" . $uri;
    } else {
        $site = substr($options->siteUrl, 0, strlen($options->siteUrl) - 1);
        return $site . __TYPECHO_THEME_DIR__ . "/" . getTheme() . "/" . $uri;
    }
}

function thisThemeFile($uri)
{
    echo getThemeFile($uri);
    return;
}

function getTheme()
{
    static $themeName = NULL;
    if ($themeName === NULL) {
        $db = Typecho_Db::get();
        $query = $db->select('value')->from('table.options')->where('name = ?', 'theme');
        $result = $db->fetchAll($query);
        $themeName = $result[0]["value"];
    }
    return $themeName;
}

function getThemeOptions()
{
    static $themeOptions = "";
    if ($themeOptions == "") {
        $db = Typecho_Db::get();
        $query = $db->select('value')->from('table.options')->where('name = ?', 'theme:' . getTheme());
        $result = $db->fetchAll($query);
        $themeOptions = unserialize($result[0]["value"]);
        unset($db);
    }
    return $themeOptions;
}

function themeInit($archive)
{
    if (($archive->is('post') || $archive->is('page')) && in_array("Lazyload", getThemeOptions()["switch"])) {
        $archive->content = preg_replace('#<img(.*?) src="(.*?)" (.*?)>#',
            '<img$1 data-original="$2" class="lazy" $3>', $archive->content);
    }
    $options = Helper::options();
    if ($options->version === "1.1/17.10.30") {
        $archive->content = preg_replace('#<li><p>(.*?)</p>(.*?)</li>#',
            '<li>$1$2</li>', $archive->content);
    }
    $options->commentsAntiSpam = false;
}

/**
 * 文章缩略图
 * @param $widget $widget
 */
function showThumbnail($widget)
{
    if($widget->fields->picUrl){
        echo $widget->fields->picUrl;
        return;
    }

    //If article no include picture, display random default picture
    $rand = rand(1, $widget->widget('Widget_Options')->RandomPicAmnt); //Random number

    $random = getThemeFile('img/random/material-' . $rand . '.png');



    // If only one random default picture, delete the following "//"
    //$random = $widget->widget('Widget_Options')->themeUrl . '/img/random.jpg';

    $attach = $widget->attachments(1)->attachment;
    $pattern = '/\<img.*?src\=\"(.*?)\"[^>]*>/i';
    $patternlazy = '/\<img.*?data-original\=\"(.*?)\"[^>]*>/i';

    if (preg_match_all($pattern, $widget->content, $thumbUrl)) {
        echo $thumbUrl[1][0];
    } elseif (preg_match_all($patternlazy, $widget->content, $thumbUrl)) {
        echo $thumbUrl[1][0];
    } elseif ($attach->isImage) {
        echo $attach->url;
    } else {
        echo $random;
    }
}

/**
 * 随机缩略图
 * @param $widget $widget
 */
function randomThumbnail($widget)
{
    //If article no include picture, display random default picture
    $rand = rand(1, $widget->widget('Widget_Options')->RandomPicAmnt); //Random number

    $random = getThemeFile('img/random/material-' . $rand . '.png');

    echo $random;
}

/**
 * Console Copyrigtht
 */
function copyright()
{
    echo '<script>console.log("\n %c © Material ' . MATERIAL_VERSION . ' | https://github.com/idawnlight/typecho-theme-material %c \n","color:#455a64;background:#e0e0e0;padding:5px 0;border-top-left-radius:5px;border-bottom-left-radius:5px;","color:#455a64;background:#e0e0e0;padding:5px 0;border-top-right-radius:5px;border-bottom-right-radius:5px;")</script>';
}

/**
 * Multi-language support
 * @param string English 英文翻译
 * @param string Chinese 中文翻译
 * @param int languageIs 语言设置
 * @return string 对应翻译
 */
function tranMsg($eng, $chs, $l)
{
    return ($l == "0") ? $eng : $chs ;
}

/**
 * Pangu.PHP
 * @param string html_source
 * @return string 处理完的 html_source
 */
function pangu($html_source)
{
    $chunks = preg_split('/(<!--<nopangu>-->.*?<!--<\/nopangu>-->|<nopangu>.*?<\/nopangu>|<pre.*?\/pre>|<textarea.*?\/textarea>|<code.*?\/code>)/msi', $html_source, -1, PREG_SPLIT_DELIM_CAPTURE);
    $result = '';
    foreach ($chunks as $c) {
        if (strtolower(substr($c, 0, 16)) == '<!--<nopangu>-->') {
            $c = substr($c, 16, strlen($c) - 16 - 17);
            $result .= $c;
            continue;
        } else if (strtolower(substr($c, 0, 9)) == '<nopangu>') {
            $c = substr($c, 9, strlen($c) - 9 -10);
            $result .= $c;
            continue;
        } else if (strtolower(substr($c, 0, 4)) == '<pre' || strtolower(substr($c, 0, 9)) == '<textarea' || strtolower(substr($c, 0, 5)) == '<code') {
            $result .= $c;
            continue;
        }
        $result .= doPangu($c);
    }
    return $result;
}