<?php

namespace BsfSaasBilling;

use BsfSaasBilling\Controllers\FrontendController;
use BsfSaasBilling\Controllers\UserSyncController;

/**
 * App
 *
 * @category App
 * @package  BsfSaasBilling
 * @author   DJ <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class App
{
	/**
	 * Constructor
	 */
	public function __construct() {}

	/**
	 * Run app
	 *
	 * @return void
	 */
	public function run()
	{
		new FrontendController();
		new UserSyncController();
	}
}
