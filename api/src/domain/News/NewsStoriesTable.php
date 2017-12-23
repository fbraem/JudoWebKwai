<?php

namespace Domain\News;

class NewsStoriesTable implements NewsStoriesInterface
{
    private $db;

    private $table;

    private $select;

    public function __construct($db)
    {
        $this->db = $db;

        $this->table = new \Zend\Db\TableGateway\TableGateway('news_stories', $this->db);
        $this->select = $this->table->getSql()->select();
    }

    public function whereCategory($id)
    {
        $this->select->where(['category_id' => $id]);
        return $this;
    }

    public function whereFeatured()
    {
        $this->select->order('featured DESC');
        $this->select->where
            ->greaterThan('featured', '0')
            ->nest()
            ->isNull('featured_end_date')
            ->or
            ->greaterThan('featured_end_date', \Carbon\Carbon::now()->toDateTimeString())
            ->unnest();
        return $this;
    }

    public function whereAllowedToSee()
    {
        $this->select->where
            ->equalTo('enabled', 1)
            ->nest()
            ->isNull('publish_date')
            ->or
            ->lessThanOrEqualTo('publish_date', \Carbon\Carbon::now()->toDateTimeString())
            ->unnest();
    }

    public function wherePublished(int $year, ?int $month = null)
    {
        $this->select->where('YEAR(publish_date)', '=', $year);
        if ($month) {
            $this->select->where('MONTH(publish_date)', '=', $month);
        }
        return $this;
    }

    public function whereId($id)
    {
        $this->select->where(['id' => $id]);
        return $this;
    }

    public function orderByDate()
    {
        $this->select->order('publish_date DESC');
        return $this;
    }

    public function findOne() : ?NewsStoryInterface
    {
        $stories = $this->find();
        if ($stories && count($stories) > 0) {
            return reset($stories);
        }
        return null;
    }

    public function find(?int $limit = null, ?int $offset = null) : iterable
    {
        $this->select->columns([
            'id',
            'enabled',
            'featured',
            'featured_end_date',
            'publish_date',
            'end_date',
            'remark',
            'category_id',
            'user_id',
            'created_at',
            'updated_at'
        ]);

        if ($limit) {
            $this->select->limit($limit);
        }
        if ($offset) {
            $this->select->offset($offset);
        }

        $stories = [];
        $resultSet = $this->table->selectWith($this->select);
        foreach ($resultSet as $row) {
            $stories[$row->id] = $row;
        }

        if (count($stories) > 0) {
            $contents = (new NewsContentsTable($this->db))->forNewsId(array_keys($stories))->find();
            foreach ($contents as $newsId => $content) {
                $story = $stories[$newsId];
                $story->contents = $content;
            }

            $ids = array_unique(
                array_map(function ($v) {
                    return $v->category_id;
                }, $stories)
            );
            $categories = (new NewsCategoriesTable($this->db))->whereId($ids)->find();
            foreach ($stories as $story) {
                $story->category = $categories[$story->category_id];
            }

            $ids = array_unique(
                array_map(function ($v) {
                    return $v->user_id;
                }, $stories)
            );
            $users = (new \Domain\User\UsersTable($this->db))->whereIds($ids)->find();
            foreach ($stories as $story) {
                $story->author = $users[$story->user_id];
            }
        }

        $result = [];
        foreach ($stories as $story) {
            $result[] = new NewsStory($this->db, $story);
        }
        return $result;
    }

    public function count() : int
    {
        $this->select->columns(['c' => new \Zend\Db\Sql\Expression('COUNT(id)')]);
        $resultSet = $this->table->selectWith($this->select);
        return (int) $resultSet->current()->c;
    }

    public function archive() : iterable
    {
        $archive = $this->table->getSql()->select();
        $archive->columns([
            'year' => new \Zend\Db\Sql\Expression('YEAR(publish_date)'),
            'month' => new \Zend\Db\Sql\Expression('MONTH(publish_date)'),
            'count' => new \Zend\Db\Sql\Expression('COUNT(id)')
        ]);
        $archive->where->equalTo('enabled', 1);
        $archive->group(['year', 'month']);
        $archive->order(['year DESC', 'month DESC']);

        $result = [];
        $resultSet = $this->table->selectWith($archive);
        foreach ($resultSet as $row) {
            $result[] = $row;
        }
        return $result;
    }
}
