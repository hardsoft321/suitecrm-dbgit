<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package dbgit
 */

class SugarBeanDbGitTableRecord
{
    protected $tableDefs;
    protected $bean;

    public function __construct($tableDefs)
    {
        $this->tableDefs = $tableDefs;
        $this->bean = BeanFactory::newBean($this->tableDefs['module']);
    }

    public function retrieveByFields($row)
    {
        $this->bean->retrieve_by_string_fields($row);
    }

    public function isLoaded()
    {
        return !empty($this->bean) && !empty($this->bean->id);
    }

    public function getId()
    {
        return $this->bean->id;
    }

    public function setField($name, $value)
    {
        $this->bean->$name = $value;
    }

    public function save()
    {
        $this->bean->save();
    }

    public function delete()
    {
        $this->bean->mark_deleted($this->bean->id);
    }
}
