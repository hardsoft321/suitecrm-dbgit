<?php
/**
 * @license http://hardsoft321.org/license/ GPLv3
 * @author Evgeny Pervushin <pea@lab321.ru>
 * @package dbgit
 */

class DbGitTableRecord
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
        $query = "SELECT * FROM $table ".$this->getWhere();
        $result = $this->db->query($query, false, "Looking For Duplicate Relationship:" . $query);
        $this->row=$this->db->fetchByAssoc($result);
    }

    public function isLoaded()
    {
        return !empty($this->row);
    }

    public function getId()
    {
        $index = reset($this->tableDefs['indices']);
        if(count($index['fields']) > 1) {
            throw new Exception("Get id: Composed primary key not supported yet");
        }
        $name = reset($index['fields']);
        return $this->row[$name];
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

        if(empty($this->row)) {
            $relate_values = array_merge($relate_values,$data_values);
            $query = "INSERT INTO $table (". implode(',', array_keys($relate_values)) . ") VALUES (" . "'" . implode("', '", $relate_values) . "')" ;

            $this->db->query($query, false, "Creating Table:" . $query);
        }
        else {
            $conds = array();
            foreach($data_values as $key=>$value)
            {
                array_push($conds,$key."='".$this->db->quote($value)."'");
            }
            $query = "UPDATE $table SET ". implode(',', $conds)." ".$this->getWhere();
            $this->db->query($query, false, "Updating Table:" . $query);
        }
    }

    public function delete()
    {
        $table = $this->tableDefs['table'];
        $query = "DELETE FROM $table ".$this->getWhere();
        $this->db->query($query, false,"Error on record deletion");
    }

    protected function getWhere()
    {
        $where = "WHERE 1=1 ";
        foreach($this->relate_values as $name=>$value)
        {
            $where .= " AND $name = '$value' ";
        }
        return $where;
    }
}
