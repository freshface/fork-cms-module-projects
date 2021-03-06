<?php

namespace Backend\Modules\Projects\Ajax;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Backend\Core\Engine\Base\AjaxAction as BackendBaseAJAXAction;
use Backend\Modules\Tags\Engine\Model as BackendTagsModel;

/**
 * This is the autocomplete-action, it will output a list of tags that start
 * with a certain string.
 */
class GetAllTags extends BackendBaseAJAXAction
{
    /**
     * Execute the action
     */
    public function execute()
    {
        $language = strtolower(\SpoonFilter::getPostValue('language', null, 'en', 'string'));
        parent::execute();
        $this->output(self::OK, BackendTagsModel::getAll($language));
    }
}
