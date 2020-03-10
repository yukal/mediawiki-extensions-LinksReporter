<?php

// use Wikimedia\Rdbms\DBQueryError;

class ApiLinksReporter extends ApiBase {

    /** @inheritDoc */
    public function execute() {
        exit();
        $wgExtensionDirectory = $this->getConfig()->get('ExtensionDirectory');
        $cacheFile = $wgExtensionDirectory 
            . DIRECTORY_SEPARATOR . 'LinksReporter'
            . DIRECTORY_SEPARATOR . 'cache.json'
        ;

        $params = $this->extractRequestParams();

        $actType = $params['type'];
        $title = Title::newFromID($params['aid']);

        if ( is_null( $title ) ) {
            $this->dieWithError( [ 'linksreporter-nosuch-article-id', $params['aid'] ], 'nosuch-aid' );
        }

        if (!$actType) {
            $this->dieWithError( [ 'linksreporter-nosuch-act-type', $params['type'] ], 'nosuch-act-type' );
        }

        $articleId = $title->getArticleID();

        $contents = file_get_contents($cacheFile);
        $record = json_decode($contents, true);

        if ($actType == 'del') {
            unset( $record[ $articleId ] );
        }

        if ($actType == 'add') {
            $record[ $articleId ] = [ $title->getText(), $title->getFullURL() ];
        }

        $contents = json_encode($record);

        if (file_put_contents($cacheFile, $contents)) {
            echo json_encode([ 'success' => true, 'aid' => $articleId ]);
            // echo $articleId;
        } else {
            echo json_encode([ 'success' => false ]);
        }
    }

    /** @inheritDoc */
    public function getAllowedParams() {
        return [
            'aid' => [
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_REQUIRED => true,
            ],
            'type' => [
                ApiBase::PARAM_TYPE => 'string',
                ApiBase::PARAM_REQUIRED => true,
            ],
            // 'token' => [
            // 	ApiBase::PARAM_TYPE => 'string',
            // 	ApiBase::PARAM_REQUIRED => true,
            // ],
        ];
    }

    /** @inheritDoc */
    public function needsToken() {
        // return 'csrf';
        return false;
    }

    /** @inheritDoc */
    public function isWriteMode() {
        return true;
    }

    /** @inheritDoc */
    protected function getExamplesMessages() {
        return [
            'action=linksreporter&type=add&aid=142&token=123ABC'
                => 'apihelp-linksreporter-example-1',
        ];
    }
}
