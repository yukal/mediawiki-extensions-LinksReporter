<?php

/**
 * LinksReporter Special Page
 * Aliases for Special:LinksReporter
 * https://www.mediawiki.org/wiki/Manual:Special_pages
 * https://www.mediawiki.org/wiki/Manual:Special_pages#The_special_page_file
 * https://doc.wikimedia.org/mediawiki-core/1.31.0/php/classSpecialPage.html
 *
 * @file
 * @ingroup Extensions
 * @version 1.0
 * @author Alexander Yukal <yukal@email.ua>
 * @license https://opensource.org/licenses/MIT MIT License
 */

class LinksReporterPage extends SpecialPage {
    protected $wmLinksReporterData = [];

    /**
     * __construct
     * @see https://doc.wikimedia.org/mediawiki-core/1.31.0/php/classSpecialPage.html#ae9deb76f4b3ab28b834e3206491e2322
     *
     * @param  string $name Name of the special page, as seen in links and URLs
     * @param  string $restriction User right required, e.g. "block" or "delete
     * @param  bool $listed Whether the page is listed in Special:Specialpages
     * @param  callable|bool $function Unused
     * @param  string $file Unused
     * @param  bool $includable Whether the page can be included in normal pages
     *
     * @return void
     */
    //function __construct($name='', $restriction='', $listed=true, $function=false, $file='', $includable=false) {
    function __construct() {
        parent::__construct( 'LinksReporter' );
    }

    /**
     * @param string|null $par
     */
    protected function setup( $par ) {
        $timestamp = date('Ymd000000', strtotime('-40 day', time()));

        $opts = new FormOptions();
        $this->opts = $opts; // bind
        $opts->add( 'hideliu', false );
        $opts->add( 'hidepatrolled', $this->getUser()->getBoolOption( 'newpageshidepatrolled' ) );
        $opts->add( 'hidebots', false );
        $opts->add( 'hideredirs', true );
        $opts->add( 'limit', $this->getUser()->getIntOption( 'rclimit' ) );
        $opts->add( 'offset', '' );
        $opts->add( 'namespace', '0' );
        $opts->add( 'username', '' );
        $opts->add( 'feed', '' );
        $opts->add( 'tagfilter', '' );
        $opts->add( 'invert', false );
        $opts->add( 'size-mode', 'max' );
        $opts->add( 'size', 0 );

        $opts->add( 'rctype', 1 );
        $opts->add( 'timestamp', $timestamp );
        $opts->add( 'timestamp-mode', 'max' );

        $this->customFilters = [];
        foreach ( $this->customFilters as $key => $params ) {
            $opts->add( $key, $params['default'] );
        }

        $opts->fetchValuesFromRequest( $this->getRequest() );
        if ( $par ) {
            $this->parseParams( $par );
        }

        $opts->validateIntBounds( 'limit', 0, 5000 );
    }

    /**
     * execute
     * @see https://doc.wikimedia.org/mediawiki-core/1.31.0/php/classSpecialPage.html#adaa2cce21133f53bdbdb306cfbda20a4
     *
     * @param  string|null $par The subpage component of the current title
     *
     * @return void
     */
    function execute( $par ) {
        // $config = $this->getConfig();
        $wgExtensionDirectory = $this->getConfig()->get('ExtensionDirectory');

        // $request = $this->getRequest();
        $output = $this->getOutput();
        $user = $this->getUser();

        $userName = $user->getName();
        $userGroups = $user->getGroups();
        $isAdmin = in_array('sysop', $userGroups) && $userName=='Admin';

        $this->setHeaders();

        // Do stuff
        // ...

        // $output->enableOOUI();
        $output->addModules([
            'ext.LinksReporter',
            // 'oojs-ui-core',
            // 'oojs-ui-widgets',
            // 'oojs-ui-windows',
        ]);

        $msgAddedPages = wfMessage( 'linksreporter-added-pages' );
        $msgNoArticles = wfMessage( 'linksreporter-no-articles' );
        $msgPagesInCache = wfMessage( 'linksreporter-pages-in-cache' );

        $output->setPagetitle( $msgAddedPages );

        $path = $wgExtensionDirectory 
            . DIRECTORY_SEPARATOR . 'LinksReporter'
            . DIRECTORY_SEPARATOR . 'cache.json'
        ;
        $contents = file_get_contents($path);
        $this->wmLinksReporterData = json_decode($contents, true);

        if ($isAdmin && $user->isLoggedIn()) {
            $this->setup( $par );

            $pager = new LinksReporterPager( $this, $this->opts );
            $pager->mLimit = $this->opts->getValue( 'limit' );
            $pager->mOffset = $this->opts->getValue( 'offset' );

            if ( $pager->getNumRows() ) {
                $content = '';

                if ($navigation = $pager->getNavigationBar()) {
                    $content = $navigation ."<br/><br/>". $pager->getBody() ."<br/>". $navigation;
                } else {
                    $content = $pager->getBody();
                }

                $output->addHTML( $content );
                $output->addHTML( "<br><hr><br>" );

                // add styles for change tags
                // $output->addModuleStyles( 'mediawiki.interface.helpers.styles' );
            } else {
                //$output->addWikiMsg( 'specialpage-empty' );
                $output->addHTML( $msgNoArticles );
            }
        }

        if ($isAdmin && $user->isLoggedIn()) {
            $output->addWikiText( $msgPagesInCache );
        }

        $output->addHTML( '<div class="linksreporter-rows">' );

        foreach ( $this->wmLinksReporterData as $key => $row ) {
            list($articleName, $articleLink) = $row;

            $link = Html::element( 'a', ['href' => $articleLink], $articleName );
            $ret = "{$link}";

            if ($isAdmin && $user->isLoggedIn()) {
                $btnDel = Html::linkButton('-', [
                    'href' => '#', 
                    'class' => 'linksreporter-btn-minus', 
                    'data-mw-aid' => $key
                ]);
                $ret = "{$btnDel} {$link}";
            }

            $output->addHTML( Html::rawElement( 'div', ['class'=>'row'], $ret ) );
        }

        $output->addHTML( '</div>' );
    }

    /**
     * Format a row, providing the timestamp, links to the page/history,
     * size, user links, and a comment
     *
     * @param object $result Result row
     * @return string
     */
    public function formatRow( $result ) {
        $linkRenderer = $this->getLinkRenderer();
        $title = Title::newFromRow( $result );

        $articleId = $title->getArticleID();

        $classes = [];
        $attribs = [ 'data-mw-revid' => $result->rev_id ];

        $lang = $this->getLanguage();
        $dm = $lang->getDirMark();

        $exsists = array_key_exists($articleId, $this->wmLinksReporterData);

        $spanTime = Html::element( 'span', [ 'class' => 'mw-newpages-time' ],
            $lang->userTime( $result->rc_timestamp, $this->getUser() )
        );

        $btnAction = $exsists
            ? Html::linkButton('-', [ 
                'href' => '#', 
                'class' => 'linksreporter-btn-minus', 
                'data-mw-aid' => $articleId
            ])
            : Html::linkButton('+', [ 
                'href' => '#', 
                'class' => 'linksreporter-btn-pluse', 
                'data-mw-aid' => $articleId
            ])
        ;

        $plink = $linkRenderer->makeKnownLink($title);

        # Add a class for zero byte pages
        if ( $result->length == 0 ) {
            $classes[] = 'mw-newpages-zero-byte-page';
        }

        # Tags, if any.
        if ( isset( $result->ts_tags ) ) {
            list( $tagDisplay, $newClasses ) = ChangeTags::formatSummaryRow(
                $result->ts_tags,
                'newpages',
                $this->getContext()
            );
            $classes = array_merge( $classes, $newClasses );
        }

        $classes[] = 'row';

        if ($exsists) {
            $classes[] = 'linksreporter-row-added';
        }

        $ret = "{$btnAction} {$spanTime} {$dm} {$plink}";

        // Let extensions add data
        $attribs = array_filter( $attribs,
            [ Sanitizer::class, 'isReservedDataAttribute' ],
            ARRAY_FILTER_USE_KEY
        );

        if (count( $classes )) {
            $attribs['class'] = implode( ' ', $classes );
        }

        return Html::rawElement( 'div', $attribs, $ret ) . "\n";
    }

    /**
     * getGroupName
     *
     * @return string
     */
    function getGroupName() {
        return 'pagetools';
        // return 'other';
        // return 'media';
    }
}
