<script>
function getCookie(name) {
    // Split cookie string and get all individual name=value pairs in an array
    var cookieArr = document.cookie.split(";");
    
    // Loop through the array elements
    for(var i = 0; i < cookieArr.length; i++) {
        var cookiePair = cookieArr[i].split("=");
        
        /* Removing whitespace at the beginning of the cookie name
        and compare it with the given string */
        if(name == cookiePair[0].trim()) {
            // Decode the cookie value and return
            return decodeURIComponent(cookiePair[1]);
        }
    }
    
    // Return null if not found
    return null;
}

class LanguagePreference {
    constructor() {
        this.language = getCookie('language-preference')
    }
    write() {
        document.cookie = 'language-preference=' + this.language + '; expires=; path=/; domain=DGW.ltd; sameSite=lax'
    }
}
//Language functions
const languagemenuhandler = (redirect_url, language_id, language_name)=>{
        var lang = new LanguagePreference()
        lang.language = language_id.toLowerCase()
        lang.write()
};

const languagecheck =(page_language_id, multilingual_campaign)=>{
    var lang = new LanguagePreference()
    if (lang.language != page_language_id && lang.language in multilingual_campaign) {
        document.location = multilingual_campaign[lang.language]
    }
};

if (languagecheck) {
languagecheck({
<?php
$language_json = '';
foreach ($languages as $l) {
    if (($l['url'] != home_url().'/'.$l['slug'].'/') && ($l['url'] != site_url()) || is_front_page()) {
       $language_json = $language_json."'".$l['slug']."'".': "'.$l['url'].'",'."\n";
    }
}
echo rtrim($language_json, ',');
?>
})
}
</script>

<ul id="language-menu" class="dgwltd-menu-language">
<?php foreach($languages as $l) { ?>
    <li>
    <?php if($l['current_lang']) : ?>
        <span class="active"><?php echo $l['slug'] ?></span>
    <?php else : ?>
        <?php if( is_page_template( 'template-layout-header.php' ) ) : ?>
            <a href="<?php echo pll_home_url( $l['slug'] ); ?>" onClick="languagemenuhandler('<?php echo $l['url']; ?>', '<?php echo $l['slug'] ?>', '<?php echo $l['name'] ?>')"><?php echo $l['slug']; ?></a>
        <?php else : ?>
            <a href="<?php echo $l['url']; ?>" onClick="languagemenuhandler('<?php echo $l['url']; ?>', '<?php echo $l['slug'] ?>', '<?php echo $l['name'] ?>')"><?php echo $l['slug']; ?></a>
        <?php endif; ?>
    <?php endif ?>
    </li>
<?php } ?>
</ul>