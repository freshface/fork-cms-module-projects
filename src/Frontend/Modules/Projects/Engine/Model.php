<?php

namespace Frontend\Modules\Projects\Engine;

use Frontend\Core\Engine\Model as FrontendModel;
use Frontend\Core\Engine\Language;
use Frontend\Core\Engine\Navigation;
use Frontend\Modules\Projects\Engine\Images as FrontendProjectsImagesModel;

/**
 * In this file we store all generic functions that we will be using in the Projects module
 *
 * @author Frederik Heyninck <frederik@figure8.be>
 */
class Model
{
    /**
    * Get the number of items
    *
    * @return int
    */
   public static function getAllCount($filter = array())
   {
       $query =
            'SELECT COUNT(i.id) AS count
             FROM projects AS i';

      // init parameters
      $parameters = array();

       if (isset($filter['categories']) && $filter['categories'] !== null && count($filter['categories'])) {
           $query .= ' INNER JOIN projects_linked_catgories AS c ON i.id = c.project_id';
       }

       $query .= ' WHERE 1';

       $query .= ' AND i.hidden = ?';
       $parameters[] = 'N';

       $query .= ' AND i.status = ?';
       $parameters[] = 'active';

       $query .= ' AND i.publish_on <= ?';
       $parameters[] = FrontendModel::getUTCDate('Y-m-d H:i') . ':00';

       if (isset($filter['categories']) && $filter['categories'] !== null && count($filter['categories'])) {
           $query .= ' AND c.category_id IN(' . implode(',', array_values($filter['categories'])) . ')';
       }

      //$query .= ' GROUP BY i.id';

      return (int) FrontendModel::get('database')->getVar($query, $parameters);
   }

    public static function get($URL)
    {
        $URL = (string) $URL;
        $item = (array) FrontendModel::getContainer()->get('database')->getRecord(
           'SELECT i.id, i.image, c.name, c.url, c.description,
            c.seo_description, c.seo_title, c.seo_description_overwrite, c.seo_title_overwrite
            FROM projects AS i
            JOIN project_content AS c on c.project_id = i.id
            WHERE i.status = ? AND i.publish_on <= ? AND c.url = ? AND c.language = ? AND i.hidden = ?',
           array(
              'active',
              FrontendModel::getUTCDate('Y-m-d H:i') . ':00',
               $URL,
               FRONTEND_LANGUAGE,
               'N'
           )
       );

       // no results?
       if (empty($item)) {
           return array();
       }

       // init var
       $link = Navigation::getURLForBlock('Projects', 'Detail');
        $item['full_url'] = $link . '/' . $item['url'];
        $item['images'] = FrontendProjectsImagesModel::getAll($item['id']);

       // return
       return $item;
    }

    public static function getById($id)
    {
        $id = (int) $id;
        $item = (array) FrontendModel::getContainer()->get('database')->getRecord(
           'SELECT i.id, i.image, c.name, c.url, c.description,
            c.seo_description, c.seo_title, c.seo_description_overwrite, c.seo_title_overwrite
            FROM projects AS i
            JOIN project_content AS c on c.project_id = i.id
            WHERE i.status = ? AND i.publish_on <= ? AND i.id = ? AND c.language = ? AND i.hidden = ?',
           array(
              'active',
              FrontendModel::getUTCDate('Y-m-d H:i') . ':00',
               $id,
               FRONTEND_LANGUAGE,
               'N'
           )
       );

       // no results?
       if (empty($item)) {
           return array();
       }

       // init var
       $link = Navigation::getURLForBlock('Projects', 'Detail');
        $item['full_url'] = $link . '/' . $item['url'];
        $item['images'] = FrontendProjectsImagesModel::getAll($item['id']);

       // return
       return $item;
    }

    public static function getDraft($URL)
    {
        $URL = (string) $URL;
        $item = (array) FrontendModel::getContainer()->get('database')->getRecord(
           'SELECT i.id, i.image, c.name, c.url, c.description
            FROM projects AS i
            JOIN project_content AS c on c.project_id = i.id
            WHERE c.url = ? AND c.language = ?',
           array(
               $URL,
               FRONTEND_LANGUAGE,
           )
       );
       // no results?
       if (empty($item)) {
           return array();
       }

       // init var
       $link = Navigation::getURLForBlock('Projects', 'Detail');
        $item['full_url'] = $link . '/' . $item['url'];
        $item['images'] = FrontendProjectsImagesModel::getAll($item['id']);

       // return
       return $item;
    }

   /**
     * Get all items (at least a chunk)
     *
     * @param int $limit  The number of items to get.
     * @param int $offset The offset.
     * @return array
     */
    public static function getAll($limit = 10, $offset = 0, $filter = array())
    {
        $query = 'SELECT i.id, i.image, co.name, co.url, co.description
             FROM projects AS i
             JOIN project_content AS co on co.project_id = i.id';

        // init parameters
        $parameters = array();

        if (isset($filter['categories']) && $filter['categories'] !== null && count($filter['categories'])) {
            $query .= ' INNER JOIN projects_linked_catgories AS c ON i.id = c.project_id';
        }

        $query .= ' WHERE 1';

        $query .= ' AND i.hidden = ?';
        $parameters[] = 'N';

        $query .= ' AND i.status = ?';
        $parameters[] = 'active';

        $query .= ' AND co.language = ?';
        $parameters[] = FRONTEND_LANGUAGE;

        $query .= ' AND i.publish_on <= ?';
        $parameters[] = FrontendModel::getUTCDate('Y-m-d H:i') . ':00';

        if (isset($filter['categories']) && $filter['categories'] !== null && count($filter['categories'])) {
            $query .= ' AND c.category_id IN(' . implode(',', array_values($filter['categories'])) . ')';
        }


        if (isset($filter['ignore_projects']) && $filter['ignore_projects'] !== null) {
            $query .= ' AND i.id NOT IN(' . implode(',', array_values($filter['ignore_projects'])) . ')';
        }


        $query .= ' GROUP BY i.id ORDER BY i.sequence DESC, i.id DESC LIMIT ?, ?';
        $parameters[] = (int) $offset;
        $parameters[] = (int) $limit;

        $items = (array) FrontendModel::get('database')->getRecords($query, $parameters);

        // no results?
        if (empty($items)) {
            return array();
        }

        // get detail action url
        $detailUrl = Navigation::getURLForBlock('Projects', 'Detail');

        // prepare items for search
        foreach ($items as &$item) {
            $item['full_url'] =  $detailUrl . '/' . $item['url'];
        }


        // return
        return $items;
    }


    public static function getNavigation($id)
    {
        // redefine
        $id = (int) $id;

        // get db
        $db = FrontendModel::getContainer()->get('database');

        // get date for current item
        $currentSequence = (string) $db->getVar(
            'SELECT i.sequence
             FROM projects AS i
             WHERE i.id = ?',
            array($id)
        );

        // validate
        if ($currentSequence == '') {
            return array();
        }

        // init var
        $navigation = array();
        $detailLink = Navigation::getURLForBlock('Projects', 'Detail') . '/';

        // get previous post
        $navigation['previous'] = $db->getRecord(
            'SELECT i.id, m.name, CONCAT(?, m.url) AS url
             FROM projects AS i
             INNER JOIN project_content AS m ON i.id = m.project_id
             WHERE i.id != ? AND i.publish_on <= ? AND i.status = ? AND i.hidden = ? AND m.language = ? AND
                ((i.sequence = ? AND i.id < ?) OR i.sequence < ?)
             ORDER BY i.sequence DESC, i.id DESC
             LIMIT 1',
            array($detailLink, $id, FrontendModel::getUTCDate('Y-m-d H:i') . ':00', 'active', 'N', FRONTEND_LANGUAGE, $currentSequence, $id, $currentSequence)
        );

        // get next post
        $navigation['next'] = $db->getRecord(
            'SELECT i.id, m.name, CONCAT(?, m.url) AS url
             FROM projects AS i
             INNER JOIN project_content AS m ON i.id = m.project_id
             WHERE i.id != ? AND i.publish_on <= ? AND i.status = ? AND i.hidden = ? AND m.language = ? AND
                ((i.sequence = ? AND i.id > ?) OR i.sequence > ?)
             ORDER BY i.sequence ASC, i.id ASC
             LIMIT 1',
            array($detailLink, $id,FrontendModel::getUTCDate('Y-m-d H:i') . ':00', 'active', 'N',  FRONTEND_LANGUAGE, $currentSequence, $id, $currentSequence)
        );

        // if empty, unset it
       if (empty($navigation['previous'])) {
           unset($navigation['previous']);
       }
        if (empty($navigation['next'])) {
            unset($navigation['next']);
        }

        // return
        return $navigation;
    }


    /**
     * Parse the search results for this module
     *
     * Note: a module's search function should always:
     *        - accept an array of entry id's
     *        - return only the entries that are allowed to be displayed, with their array's index being the entry's id
     *
     *
     * @param array $ids The ids of the found results.
     *
     * @return array
     */
    public static function search(array $ids)
    {
        $items = (array) FrontendModel::getContainer()->get('database')->getRecords(
            'SELECT i.id, m.name as title, m.description as text,  m.url
             FROM projects AS i
             INNER JOIN project_content AS m ON i.id = m.project_id
             WHERE i.status = ? AND i.hidden = ? AND m.language = ? AND i.publish_on <= ? AND i.id IN (' .
            implode(',', $ids) . ')',
            array('active', 'N', LANGUAGE, date('Y-m-d H:i')),
            'id'
        );

        // prepare items for search
        $detailUrl = FrontendNavigation::getURLForBlock('Projects', 'Detail');
        foreach ($items as &$item) {
            $item['full_url'] = $detailUrl . '/' . $item['url'];
        }

        // return
        return $items;
    }

    /*
    public static function getNavigation($id)
    {
        // redefine
        $id = (int) $id;

        // get db
        $db = FrontendModel::getContainer()->get('database');

        // get date for current item
        $date = (string) $db->getVar(
            'SELECT i.publish_on
             FROM projects AS i
             WHERE i.id = ? AND i.status = ?',
            array($id, 'active')
        );

        // validate
        if ($date == '') {
            return array();
        }

        // validate
        if ($date == '') {
            return array();
        }

        // init var
        $navigation = array();
        $detailLink = Navigation::getURLForBlock('Projects', 'Detail') . '/';

        // get previous post
        $navigation['previous'] = $db->getRecord(
            'SELECT i.id, m.name, CONCAT(?, m.url) AS url
             FROM projects AS i
             INNER JOIN project_content AS m ON i.id = m.project_id
             WHERE i.id != ?  AND i.publish_on <= ?  AND i.status = ? AND i.hidden = ? AND m.language = ? AND
                ((i.publish_on = ? AND i.id < ?) OR i.publish_on < ?)
             ORDER BY i.publish_on DESC, i.id DESC
             LIMIT 1',
            array($detailLink, $id, FrontendModel::getUTCDate('Y-m-d H:i') . ':00', 'active','N', FRONTEND_LANGUAGE, $date, $id, $date)
        );

        // get next post
        $navigation['next'] = $db->getRecord(
            'SELECT i.id, m.name, CONCAT(?, m.url) AS url
             FROM projects AS i
             INNER JOIN project_content AS m ON i.id = m.project_id
             WHERE i.id != ?  AND i.publish_on <= ?  AND i.status = ? AND i.hidden = ? AND m.language = ? AND
                ((i.publish_on = ? AND i.id > ?) OR i.publish_on > ?)
             ORDER BY i.publish_on ASC, i.id ASC
             LIMIT 1',
            array($detailLink, $id, FrontendModel::getUTCDate('Y-m-d H:i') . ':00', 'active', 'N',  FRONTEND_LANGUAGE, $date, $id, $date)
        );

        // if empty, unset it
       if (empty($navigation['previous'])) {
           unset($navigation['previous']);
       }
       if (empty($navigation['next'])) {
           unset($navigation['next']);
       }

        // return
        return $navigation;
    }*/
}
