<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=page.list.loop
Tags=page.list.tpl:{LIST_ROW_RATINGS_DISPLAY},{LIST_ROW_RATINGS_AVERAGE},{LIST_ROW_RATINGS_COUNT}
[END_COT_EXT]
==================== */

/**
 * Displays page rating in pages list
 *
 * @package ratings
 * @version 0.7.0
 * @author Cotonti Team
 * @copyright Copyright (c) Cotonti Team 2008-2014
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL');

require_once cot_incfile('ratings', 'plug');

list ($ratings_display, $ratings_average, $ratings_count) = cot_ratings_display('page', $pag['page_id'], $pag['page_cat'], true);

$t->assign(array(
	'LIST_ROW_RATINGS_DISPLAY' => $ratings_display,
	'LIST_ROW_RATINGS_AVERAGE' => $ratings_average,
	'LIST_ROW_RATINGS_COUNT'   => $ratings_count
));
