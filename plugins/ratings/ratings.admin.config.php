<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=admin.config.edit.main
[END_COT_EXT]
==================== */

/**
 * Loads language string for enable_ratings option in Admin/Config
 *
 * @package comments
 * @version 0.7.0
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2008-2014
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL');

require_once cot_langfile('ratings', $type);
