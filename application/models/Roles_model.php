<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Open Source Web Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) 2013 - 2020, Alex Tselegidis
 * @license     http://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        http://easyappointments.org
 * @since       v1.0.0
 * ---------------------------------------------------------------------------- */

/**
 * Roles model
 *
 * Handles all the database operations of the role resource.
 *
 * @package Models
 */
class Roles_model extends EA_Model {
    /**
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'is_admin' => 'boolean',
        'appointments' => 'integer',
        'customers' => 'integer',
        'services' => 'integer',
        'users' => 'integer',
        'system_settings' => 'integer',
        'user_settings' => 'integer',
    ];

    /**
     * Save (insert or update) a role.
     *
     * @param array $role Associative array with the role data.
     *
     * @return int Returns the role ID.
     *
     * @throws InvalidArgumentException
     */
    public function save(array $role): int
    {
        $this->validate($role);

        if (empty($role['id']))
        {
            return $this->insert($role);
        }
        else
        {
            return $this->update($role);
        }
    }

    /**
     * Validate the role data.
     *
     * @param array $role Associative array with the role data.
     *
     * @throws InvalidArgumentException
     */
    public function validate(array $role)
    {
        // If a role ID is provided then check whether the record really exists in the database.
        if ( ! empty($role['id']))
        {
            $count = $this->db->get_where('roles', ['id' => $role['id']])->num_rows();

            if ( ! $count)
            {
                throw new InvalidArgumentException('The provided role ID does not exist in the database: ' . $role['id']);
            }
        }

        // Make sure all required fields are provided.
        if (
            empty($role['name'])
        )
        {
            throw new InvalidArgumentException('Not all required fields are provided: ' . print_r($role, TRUE));
        }
    }

    /**
     * Insert a new role into the database.
     *
     * @param array $role Associative array with the role data.
     *
     * @return int Returns the role ID.
     *
     * @throws RuntimeException
     */
    protected function insert(array $role): int
    {
        if ( ! $this->db->insert('roles', $role))
        {
            throw new RuntimeException('Could not insert role.');
        }

        return $this->db->insert_id();
    }

    /**
     * Update an existing role.
     *
     * @param array $role Associative array with the role data.
     *
     * @return int Returns the role ID.
     *
     * @throws RuntimeException
     */
    protected function update(array $role): int
    {
        if ( ! $this->db->update('roles', $role, ['id' => $role['id']]))
        {
            throw new RuntimeException('Could not update role.');
        }

        return $role['id'];
    }

    /**
     * Remove an existing role from the database.
     *
     * @param int $role_id Role ID.
     *
     * @throws RuntimeException
     */
    public function delete(int $role_id)
    {
        if ( ! $this->db->delete('roles', ['id' => $role_id]))
        {
            throw new RuntimeException('Could not delete role.');
        }
    }

    /**
     * Get a specific role from the database.
     *
     * @param int $role_id The ID of the record to be returned.
     *
     * @return array Returns an array with the role data.
     *
     * @throws InvalidArgumentException
     */
    public function find(int $role_id): array
    {
        if ( ! $this->db->get_where('roles', ['id' => $role_id])->num_rows())
        {
            throw new InvalidArgumentException('The provided role ID was not found in the database: ' . $role_id);
        }

        $role = $this->db->get_where('roles', ['id' => $role_id])->row_array();
        
        $this->cast($role); 
        
        return $role; 
    }

    /**
     * Get a specific field value from the database.
     *
     * @param int $role_id Role ID.
     * @param string $field Name of the value to be returned.
     *
     * @return string Returns the selected role value from the database.
     *
     * @throws InvalidArgumentException
     */
    public function value(int $role_id, string $field): string
    {
        if (empty($field))
        {
            throw new InvalidArgumentException('The field argument is cannot be empty.');
        }

        if (empty($role_id))
        {
            throw new InvalidArgumentException('The role ID argument cannot be empty.');
        }

        // Check whether the role exists.
        $query = $this->db->get_where('roles', ['id' => $role_id]);

        if ( ! $query->num_rows())
        {
            throw new InvalidArgumentException('The provided role ID was not found in the database: ' . $role_id);
        }

        // Check if the required field is part of the role data.
        $role = $query->row_array();
        
        $this->cast($role);

        if ( ! array_key_exists($field, $role))
        {
            throw new InvalidArgumentException('The requested field was not found in the role data: ' . $field);
        }

        return $role[$field];
    }

    /**
     * Get all roles that match the provided criteria.
     *
     * @param array|string $where Where conditions
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of roles.
     */
    public function get($where = NULL, int $limit = NULL, int $offset = NULL, string $order_by = NULL): array
    {
        if ($where !== NULL)
        {
            $this->db->where($where);
        }

        if ($order_by !== NULL)
        {
            $this->db->order_by($order_by);
        }

        $roles = $this->db->get('roles', $limit, $offset)->result_array();
        
        foreach($roles as &$role)
        {
            $this->cast($role); 
        }
        
        return $roles;
    }

    /**
     * Get the permissions array by role slug.
     *
     * The permission numbers are converted into boolean values of the four main actions:
     *
     * - view
     * - add
     * - edit
     * - delete
     *
     * After checking each individual value, you can make sure if the user is able to perform each action or not.
     *
     * @param string $slug Role slug.
     *
     * @return array Returns the permissions value.
     */
    public function get_permissions_by_slug(string $slug): array
    {
        $role = $this->db->get_where('roles', ['slug' => $slug])->row_array();
        
        $this->cast($role);

        unset(
            $role['id'],
            $role['name'],
            $role['slug'],
            $role['is_admin']
        );

        // Convert the integer values to boolean.

        $permissions = [];

        foreach ($role as $resource => $value)
        {
            $permissions[$resource] = [
                'view' => FALSE,
                'add' => FALSE,
                'edit' => FALSE,
                'delete' => FALSE
            ];

            if ($value > 0)
            {
                if ((int)($value / PRIV_DELETE) === 1)
                {
                    $permissions[$resource]['delete'] = TRUE;
                    $value -= PRIV_DELETE;
                }

                if ((int)($value / PRIV_EDIT) === 1)
                {
                    $permissions[$resource]['edit'] = TRUE;
                    $value -= PRIV_EDIT;
                }

                if ((int)($value / PRIV_ADD) === 1)
                {
                    $permissions[$resource]['add'] = TRUE;
                }

                $permissions[$resource]['view'] = TRUE;
            }
        }

        return $permissions;
    }

    /**
     * Get the query builder interface, configured for use with the roles table.
     *
     * @return CI_DB_query_builder
     */
    public function query(): CI_DB_query_builder
    {
        return $this->db->from('roles');
    }

    /**
     * Search roles by the provided keyword.
     *
     * @param string $keyword Search keyword.
     * @param int|null $limit Record limit.
     * @param int|null $offset Record offset.
     * @param string|null $order_by Order by.
     *
     * @return array Returns an array of roles.
     */
    public function search(string $keyword, int $limit = NULL, int $offset = NULL, string $order_by = NULL): array
    {
        $roles = $this
            ->db
            ->select()
            ->from('roles')
            ->like('name', $keyword)
            ->or_like('slug', $keyword)
            ->limit($limit)
            ->offset($offset)
            ->order_by($order_by)
            ->get()
            ->result_array();

        foreach($roles as &$role)
        {
            $this->cast($role);
        }

        return $roles;
    }

    /**
     * Attach related resources to a role.
     *
     * @param array $role Associative array with the role data.
     * @param array $resources Resource names to be attached.
     *
     * @throws InvalidArgumentException
     */
    public function attach(array &$role, array $resources)
    {
        // Roles do not currently have any related resources. 
    }
}
