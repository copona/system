<?php

namespace Copona\System\Library\Cart;

class User
{
    private $user_id;
    private $user_group_id;
    private $username;
    private $password;
    private $salt;
    private $permission = array();

    public function __construct($registry)
    {
        $this->db = $registry->get('db');
        $this->request = $registry->get('request');
        $this->session = $registry->get('session');

        if (isset($this->session->data['user_id'])) {
            $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE user_id = '" . (int)$this->session->data['user_id'] . "' AND status = '1'");

            if ($user_query->num_rows) {
                $this->user_id = $user_query->row['user_id'];
                $this->username = $user_query->row['username'];
                $this->password = $user_query->row['password'];
                $this->user_group_id = $user_query->row['user_group_id'];

                $this->db->query("UPDATE " . DB_PREFIX . "user SET ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "' WHERE user_id = '" . (int)$this->session->data['user_id'] . "'");

                $user_group_query = $this->db->query("SELECT permission FROM " . DB_PREFIX . "user_group WHERE user_group_id = '" . (int)$user_query->row['user_group_id'] . "'");

                $permissions = json_decode($user_group_query->row['permission'], true);

                if (is_array($permissions)) {
                    foreach ($permissions as $key => $value) {
                        $this->permission[$key] = $value;
                    }
                }
            } else {
                $this->logout();
            }
        }
    }

    public function login($username, $password)
    {
        $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE username = '" . $this->db->escape($username) . "' AND status = '1' limit 1");

        if (!$user_query->num_rows) {
            return false;
        }

        $this->password = $user_query->row['password'];
        $this->salt = $user_query->row['salt'];

        if (mb_strlen($this->password) == 32 && $this->password == md5($password) || mb_strlen($this->password) == 40 && $this->password == sha1($this->salt . sha1($this->salt . sha1($password)))) {
            $password_update = $this->db->query("UPDATE " . DB_PREFIX . "user SET password = '" . password_hash($password, PASSWORD_DEFAULT) . "' WHERE user_id = '" . $user_query->row['user_id'] . "'");

            $user_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE username = '" . $this->db->escape($username) . "' AND status = '1' limit 1");
            $this->password = $user_query->row['password'];
        }

        if (password_verify($password, $this->password)) {

            $this->session->data['user_id'] = $user_query->row['user_id'];
            $this->user_id = $user_query->row['user_id'];
            $this->username = $user_query->row['username'];
            $this->user_group_id = $user_query->row['user_group_id'];

            $user_group_query = $this->db->query("SELECT permission FROM " . DB_PREFIX . "user_group WHERE user_group_id = '" . (int)$user_query->row['user_group_id'] . "'");

            $permissions = json_decode($user_group_query->row['permission'], true);

            if (is_array($permissions)) {
                foreach ($permissions as $key => $value) {
                    $this->permission[$key] = $value;
                }
            }

            return true;
        } else {
            return false;
        }
    }

    public function logout()
    {
        unset($this->session->data['user_id']);

        $this->user_id = '';
        $this->username = '';
    }

    public function hasPermission($key, $value)
    {
        if (isset($this->permission[$key])) {
            return in_array($value, $this->permission[$key]);
        } elseif ($this->session->data['user_id'] == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function isLogged()
    {
        return $this->user_id;
    }

    public function getId()
    {
        return $this->user_id;
    }

    public function getUserName()
    {
        return $this->username;
    }

    public function getGroupId()
    {
        return $this->user_group_id;
    }

}