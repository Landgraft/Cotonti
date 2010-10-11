<?php
/**
 * Tags functions
 *
 * @package tags
 * @version 0.7.0
 * @author Trustmaster
 * @copyright Copyright (c) Cotonti Team 2008-2010
 * @license BSD
 */

defined('COT_CODE') or die('Wrong URL');

cot_require('page');
cot_require('forums');
cot_require_api('tags');
cot_require('tags', true, 'config');
cot_require_rc('tags', true);

/**
 * Parses search string into SQL query
 *
 * @param string $qs User input
 * @return string
 */
function cot_tag_parse_query($qs)
{
	$tokens1 = explode(';', $qs);
	$cnt1 = count($tokens1);
	for ($i = 0; $i < $cnt1; $i++)
	{
		$tokens2 = explode(',', $tokens1[$i]);
		$cnt2 = count($tokens2);
		for ($j = 0; $j < $cnt2; $j++)
		{
			$tag = cot_tag_prep($tokens2[$j]);
			if (!empty($tag))
			{
				if (mb_strpos($tag, '*') !== false)
				{
					$tag = str_replace('*', '%', $tag);
					$tokens2[$j] = "r.tag LIKE '$tag'";
				}
				else
				{
					$tokens2[$j] = "r.tag = '$tag'";
				}
			}
			else
			{
				return '';
			}
		}
		$tokens1[$i] = implode(' AND ', $tokens2);
	}
	$query = implode(' OR ', $tokens1);
	return $query;
}

/**
 * Global tag cloud and search form
 *
 * @param string $area Site area
 */
function cot_tag_search_form($area = 'all')
{
	global $d, $perpage, $tl, $qs, $t, $L, $R, $cfg, $db_tag_references, $tc_styles;
	$limit = ($perpage > 0) ? "$d, $perpage" : NULL;
	$tcloud = cot_tag_cloud($area, $cfg['plugin']['tags']['order'], $limit);
	$tc_html = $R['tags_code_cloud_open'];
	foreach ($tcloud as $tag => $cnt)
	{
		$tag_t = $cfg['plugin']['tags']['title'] ? cot_tag_title($tag) : $tag;
		$tag_u = cot_urlencode($tag, $cfg['plugin']['tags']['translit']);
		$tl = $lang != 'en' && $tag_u != urlencode($tag) ? '&tl=1' : '';
		foreach ($tc_styles as $key => $val)
		{
			if ($cnt <= $key)
			{
				$dim = $val;
				break;
			}
		}
		$tc_html .= cot_rc('tags_link_cloud_tag', array(
			'url' => cot_url('plug', 'e=tags&a='.$area.'&t='.$tag_u.$tl),
			'tag_title' => htmlspecialchars($tag_t),
			'dim' => $dim
		));
	}
	$tc_html .= $R['tags_code_cloud_close'];
	$t->assign('TAGS_CLOUD_BODY', $tc_html);
	$t->parse('MAIN.TAGS_CLOUD');
	if ($perpage > 0)
	{
		$where = $area == 'all' ? '' : "WHERE tag_area = '$area'";
		$sql = cot_db_query("SELECT COUNT(DISTINCT `tag`) FROM $db_tag_references $where");
		$totalitems = (int) cot_db_result($sql, 0, 0);
		$pagenav = cot_pagenav('plug','e=tags&a=' . $area, $d, $totalitems, $perpage);
		$t->assign(array(
			'TAGS_PAGEPREV' => $pagenav['prev'],
			'TAGS_PAGENEXT' => $pagenav['next'],
			'TAGS_PAGNAV' => $pagenav['main']
		));
	}
}

/**
 * Search by tag in pages
 *
 * @param string $query Search query as SQL condition
 */
function cot_tag_search_pages($query)
{
	global $t, $L, $cfg, $usr, $qs, $d, $db_tag_references, $db_pages, $o, $row;
	$totalitems = cot_db_result(cot_db_query("SELECT COUNT(*)
		FROM $db_tag_references AS r LEFT JOIN $db_pages AS p
			ON r.tag_item = p.page_id
		WHERE r.tag_area = 'pages' AND ($query) AND p.page_state = 0"), 0, 0);
	switch($o)
	{
		case 'title':
			$order = 'ORDER BY `page_title`';
		break;
		case 'date':
			$order = 'ORDER BY `page_date` DESC';
		break;
		case 'category':
			$order = 'ORDER BY `page_cat`';
		break;
		default:
			$order = '';
	}
	$sql = cot_db_query("SELECT p.page_id, p.page_alias, p.page_title, p.page_cat
		FROM $db_tag_references AS r LEFT JOIN $db_pages AS p
			ON r.tag_item = p.page_id
		WHERE r.tag_area = 'pages' AND ($query) AND p.page_id IS NOT NULL AND p.page_state = 0
		$order
		LIMIT $d, {$cfg['maxrowsperpage']}");
	$t->assign('TAGS_RESULT_TITLE', $L['tags_Found_in_pages']);
	while ($row = cot_db_fetchassoc($sql))
	{
		$tags = cot_tag_list($row['page_id']);
		$tag_list = '';
		$tag_i = 0;
		foreach ($tags as $tag)
		{
			$tag_t = $cfg['plugin']['tags']['title'] ? cot_tag_title($tag) : $tag;
			$tag_u = cot_urlencode($tag, $cfg['plugin']['tags']['translit']);
			$tl = $lang != 'en' && $tag_u != urlencode($tag) ? '&tl=1' : '';
			if ($tag_i > 0) $tag_list .= ', ';
			$tag_list .= cot_rc_link(cot_url('plug', 'e=tags&a=pages&t='.$tag_u.$tl), htmlspecialchars($tag_t), 'rel="nofollow"');
			$tag_i++;
		}
		$t->assign(array(
			'TAGS_RESULT_ROW_URL' => empty($row['page_alias']) ? cot_url('page', 'id='.$row['page_id']) : cot_url('page', 'al='.$row['page_alias']),
			'TAGS_RESULT_ROW_TITLE' => htmlspecialchars($row['page_title']),
			'TAGS_RESULT_ROW_PATH' => cot_build_catpath($row['page_cat']),
			'TAGS_RESULT_ROW_TAGS' => $tag_list
		));
		$t->parse('MAIN.TAGS_RESULT.TAGS_RESULT_ROW');
	}
	cot_db_freeresult($sql);
	$pagenav = cot_pagenav('plug','e=tags&a=pages&t=' . urlencode($qs), $d, $totalitems, $cfg['maxrowsperpage']);
	$t->assign(array(
		'TAGS_PAGEPREV' => $pagenav['prev'],
		'TAGS_PAGENEXT' => $pagenav['next'],
		'TAGS_PAGNAV' => $pagenav['main']
	));
	$t->parse('MAIN.TAGS_RESULT');
}

/**
 * Search by tag in forums
 *
 * @param string $query Search query as SQL condition
 */
function cot_tag_search_forums($query)
{
	global $t, $L, $cfg, $usr, $qs, $d, $db_tag_references, $db_forum_topics, $db_forum_sections, $o, $row;
	$totalitems = cot_db_result(cot_db_query("SELECT COUNT(*)
		FROM $db_tag_references AS r LEFT JOIN $db_forum_topics AS t
			ON r.tag_item = t.ft_id
		WHERE r.tag_area = 'forums' AND ($query)"), 0, 0);
	switch($o)
	{
		case 'title':
			$order = 'ORDER BY `ft_title`';
		break;
		case 'date':
			$order = 'ORDER BY `ft_updated` DESC';
		break;
		case 'category':
			$order = 'ORDER BY `ft_sectionid`';
		break;
		default:
			$order = '';
	}
	$sql = cot_db_query("SELECT t.ft_id, t.ft_sectionid, t.ft_title, s.fs_id, s.fs_masterid, s.fs_mastername, s.fs_title, s.fs_category
		FROM $db_tag_references AS r LEFT JOIN $db_forum_topics AS t
			ON r.tag_item = t.ft_id
		LEFT JOIN $db_forum_sections AS s
			ON t.ft_sectionid = s.fs_id
		WHERE r.tag_area = 'forums' AND ($query) AND t.ft_id IS NOT NULL
		$order
		LIMIT $d, {$cfg['maxrowsperpage']}");
	$t->assign('TAGS_RESULT_TITLE', $L['tags_Found_in_forums']);
	while ($row = cot_db_fetchassoc($sql))
	{
		$tags = cot_tag_list($row['ft_id'], 'forums');
		$tag_list = '';
		$tag_i = 0;
		foreach ($tags as $tag)
		{
			$tag_t = $cfg['plugin']['tags']['title'] ? cot_tag_title($tag) : $tag;
			$tag_u = cot_urlencode($tag, $cfg['plugin']['tags']['translit']);
			$tl = $lang != 'en' && $tag_u != urlencode($tag) ? '&tl=1' : '';
			if ($tag_i > 0) $tag_list .= ', ';
			$tag_list .= cot_rc_link(cot_url('plug', 'e=tags&a=forums&t='.$tag_u.$tl), htmlspecialchars($tag_t), 'rel="nofollow"');
			$tag_i++;
		}
		$master = ($row['fs_masterid'] > 0) ? array($row['fs_masterid'], $row['fs_mastername']) : false;
		$t->assign(array(
			'TAGS_RESULT_ROW_URL' => cot_url('forums', 'm=posts&q='.$row['ft_id']),
			'TAGS_RESULT_ROW_TITLE' => htmlspecialchars($row['ft_title']),
			'TAGS_RESULT_ROW_PATH' => cot_build_forums($row['fs_id'], cot_cutstring($row['fs_title'], 24), cot_cutstring($row['fs_category'], 16), true, $master),
			'TAGS_RESULT_ROW_TAGS' => $tag_list
		));
		$t->parse('MAIN.TAGS_RESULT.TAGS_RESULT_ROW');
	}
	cot_db_freeresult($sql);
	$pagenav = cot_pagenav('plug','e=tags&a=forums&t='.urlencode($qs), $d, $totalitems, $cfg['maxrowsperpage']);
	$t->assign(array(
		'TAGS_PAGEPREV' => $pagenav['prev'],
		'TAGS_PAGENEXT' => $pagenav['next'],
		'TAGS_PAGNAV' => $pagenav['main']
	));
	$t->parse('MAIN.TAGS_RESULT');
}

?>