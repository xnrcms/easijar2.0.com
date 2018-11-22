<?php
namespace Cart;
class Customer {
	private $customer_id;
	private $fullname;
	private $customer_group_id;
	private $email;
	private $telephone;
	private $newsletter;
	private $address_id;
	private $gender;
	private $brithday;

	public function __construct($registry) {
		$this->config = $registry->get('config');
		$this->db = $registry->get('db');
		$this->request = $registry->get('request');
		$this->session = $registry->get('session');

		if (isset($this->session->data['customer_id'])) {
			$customer_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$this->session->data['customer_id'] . "' AND status = '1'");

			if ($customer_query->num_rows) {
				$this->customer_id = $customer_query->row['customer_id'];
				$this->fullname = $customer_query->row['fullname'];
				$this->customer_group_id = $customer_query->row['customer_group_id'];
				$this->email = $customer_query->row['email'];
				$this->telephone = $customer_query->row['telephone'];
				$this->newsletter = $customer_query->row['newsletter'];
				$this->address_id = $customer_query->row['address_id'];
				$this->gender = $customer_query->row['gender'];
				$this->brithday = $customer_query->row['brithday'];

				$this->db->query("UPDATE " . DB_PREFIX . "customer SET language_id = '" . (int)$this->config->get('config_language_id') . "', ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "' WHERE customer_id = '" . (int)$this->customer_id . "'");
			} else {
				$this->logout();
			}
		}
	}

    public function login($customer, $password, $override = false) {
	    if (filter_var($customer, FILTER_VALIDATE_EMAIL)) {
            $customer_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE LOWER(email) = '" . $this->db->escape(utf8_strtolower($customer)) . "' AND status = '1'");
        } else {
		    $customer_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer WHERE customer_id = '" . (int)$customer . "' AND status = '1'");
        }

		if ($customer_query->num_rows) {
			if (!$override) {
				if (password_verify($password, $customer_query->row['password'])) {
					if (password_needs_rehash($customer_query->row['password'], PASSWORD_DEFAULT)) {
						$new_password_hashed = password_hash($password, PASSWORD_DEFAULT);
					}
				} elseif ($customer_query->row['password'] == sha1($customer_query->row['salt'] . sha1($customer_query->row['salt'] . sha1($password))) || $customer_query->row['password'] == md5($password)) {
					$new_password_hashed = password_hash($password, PASSWORD_DEFAULT);
				} else {
					return false;
				}
			}

			$this->session->data['customer_id'] = $customer_query->row['customer_id'];

			$this->customer_id = $customer_query->row['customer_id'];
			$this->fullname = $customer_query->row['fullname'];
			$this->customer_group_id = $customer_query->row['customer_group_id'];
			$this->email = $customer_query->row['email'];
			$this->telephone = $customer_query->row['telephone'];
			$this->newsletter = $customer_query->row['newsletter'];
			$this->address_id = $customer_query->row['address_id'];

			$this->db->query("UPDATE " . DB_PREFIX . "customer SET " . ((isset($new_password_hashed)) ? "salt = '', password = '" . $this->db->escape($new_password_hashed) . "', " : "") . "language_id = '" . (int)$this->config->get('config_language_id') . "', ip = '" . $this->db->escape($this->request->server['REMOTE_ADDR']) . "' WHERE customer_id = '" . (int)$this->customer_id . "'");

			return true;
		} else {
			return false;
		}
	}

	public function logout() {
		unset($this->session->data['customer_id']);

		$this->customer_id = '';
		$this->fullname = '';
		$this->customer_group_id = '';
		$this->email = '';
		$this->telephone = '';
		$this->newsletter = '';
		$this->address_id = '';
	}

	public function isLogged() {
		return $this->customer_id;
	}

	public function getId() {
		return $this->customer_id;
	}

	public function getFullName() {
		return $this->fullname;
	}

	public function getGroupId() {
		return $this->customer_group_id;
	}

	public function getEmail() {
		return $this->email;
	}

	public function getTelephone() {
		return $this->telephone;
	}

	public function getNewsletter() {
		return $this->newsletter;
	}

	public function getAddressId() {
		return $this->address_id;
	}

	public function getGender() {
		return $this->gender;
	}

	public function getBrithday() {
		return $this->brithday;
	}

	public function getBalance() {
		$query = $this->db->query("SELECT SUM(amount) AS total FROM " . DB_PREFIX . "customer_transaction WHERE customer_id = '" . (int)$this->customer_id . "'");

		return $query->row['total'];
	}

	public function getRewardPoints() {
		$query = $this->db->query("SELECT SUM(points) AS total FROM " . DB_PREFIX . "customer_reward WHERE customer_id = '" . (int)$this->customer_id . "'");

		return $query->row['total'];
	}

    public function associated($provider)
    {
        $customer = \Models\Customer::find($this->customer_id);
        return $customer->authentications()->where('provider', $provider)->count();
    }

	public function getAvatar($customer_id = 0) {
		$customer_id 	= $customer_id > 0 ? $customer_id : (int)$this->customer_id;
        $table_show = $this->db->query("show tables like '" . DB_PREFIX . "customer_authentication'")->row;
        if ($table_show) {
            $auth_image = $this->db->query("select avatar from " . DB_PREFIX . "customer_authentication where customer_id=" . (int)$customer_id)->row;
            if (isset($auth_image['avatar']) && !empty($auth_image['avatar'])) {
                return $auth_image['avatar'];
            }
        }
		$avatar = "avatar/{$customer_id}.jpg";
		if (is_file(DIR_IMAGE . $avatar)) {
			return $avatar;
		}
		return 'avatar/default-avatar.png';
	}
}
