<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package dbgit
 */

/**
 * Based on SugarBean::set_relationship
 *
 * Используется для таблиц, в которых есть поля
 *    id - CHAR(36)
 *    date_modified
 *    deleted
 */
class SugarRelDbGitTableRecord
{
    protected $tableDefs;
    protected $row;
    protected $relate_values = array();
    protected $fields = array();
    protected $db;

    public function __construct($tableDefs)
    {
        $this->tableDefs = $tableDefs;
        $this->db = $GLOBALS['db'];
    }

    public function retrieveByFields($relate_values)
    {
        $this->relate_values = $relate_values;
        $table = $this->tableDefs['table'];
        $query = "SELECT * FROM $table ";
        $where = "WHERE deleted = '0'  ";
        foreach($relate_values as $name=>$value)
        {
            $where .= " AND $name = '$value' ";
        }
        $query .= $where;
        $result = $this->db->query($query, true, "Looking For Duplicate Relationship:" . $query);
        $this->row=$this->db->fetchByAssoc($result);
    }

    public function isLoaded()
    {
        return !empty($this->row);
    }

    public function getId()
    {
        return $this->row['id'];
    }

    public function setField($name, $value)
    {
        $this->fields[$name] = $value;
    }

    public function save()
    {
        $table = $this->tableDefs['table'];
        $relate_values = $this->relate_values;
        $data_values = $this->fields;
        $date_modified = $this->db->convert("'".$GLOBALS['timedate']->nowDb()."'", 'datetime');

        if(empty($this->row['id'])) {
            unset($relate_values['id']);
            if ( isset($data_values))
            {
                $relate_values = array_merge($relate_values,$data_values);
            }
            $relate_values_quoted = array_map(function($value) {
                return $value === null ? "NULL" : "'$value'";
            }, $relate_values);
            $query = "INSERT INTO $table (id, ". implode(', ', array_keys($relate_values)) . ", date_modified) VALUES ('"
                . create_guid() . "', " . implode(", ", $relate_values_quoted) . ", ".$date_modified.")" ;

            $this->db->query($query, true, "Creating Relationship:" . $query);
        }
        else {
            $where = "WHERE deleted = '0'  ";
            foreach($relate_values as $name=>$value)
            {
                $where .= " AND $name = " . ($value === NULL ? "NULL" : "'$value'") . " ";
            }

            $conds = array();
            foreach($data_values as $key=>$value)
            {
                array_push($conds,$key."='".$this->db->quote($value)."'");
            }
            $query = "UPDATE $table SET ". implode(',', $conds).",date_modified=".$date_modified." ".$where;
            $this->db->query($query, true, "Updating Relationship:" . $query);
        }
    }

    public function delete()
    {
        $table = $this->tableDefs['table'];
        $date_modified = $this->db->convert("'".$GLOBALS['timedate']->nowDb()."'", 'datetime');
        $id = $this->row['id'];
        $query = "UPDATE $table set deleted=1 , date_modified = $date_modified where id='$id'";
        $this->db->query($query, true,"Error marking record deleted: ");
    }
}
