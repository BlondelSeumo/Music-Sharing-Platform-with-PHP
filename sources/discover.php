<?php 

$final_data = [];
$interests = [];
if (IS_LOGGED) {
    $interests = getUserInterest();

    $db->where("user_id NOT IN (SELECT user_id FROM blocks WHERE blocked_id = $user->id)");
}
$getOnePlaylist = $db->where("id IN (SELECT playlist_id FROM " . T_PLAYLIST_SONGS . ")")->where('privacy', 0)->orderby('RAND()')->getOne(T_PLAYLISTS);

if (!empty($getOnePlaylist)) {
	$final_data[] = [
		'title' => $getOnePlaylist->name,
		'thumbnail' => getMedia($getOnePlaylist->thumbnail),
		'url' => getLink('playlist/' . $getOnePlaylist->uid),
		'ajax_url' => 'playlist/' . $getOnePlaylist->uid
	];
}

if (!empty($interests)) {
    $db->where('category_id',array_keys($interests),'IN');
}
$getOneSong = $db->where('availability', 0)->orderby('RAND()')->getOne(T_SONGS);
if (!empty($getOneSong)) {
	$final_data[] = [
		'title' => $getOneSong->title,
		'thumbnail' => getMedia($getOneSong->thumbnail),
		'url' => getLink('track/' . $getOneSong->audio_id),
		'ajax_url' => 'track/' . $getOneSong->audio_id
	];
}

if (IS_LOGGED) {
	$db->where("user_id NOT IN (SELECT user_id FROM blocks WHERE blocked_id = $user->id)");
}
$db->where("id IN (SELECT playlist_id FROM " . T_PLAYLIST_SONGS . ")")->where('privacy', 0);

if (!empty($getOnePlaylist)) {
	$db->where('id', $getOnePlaylist->id,'<>');
}

$getAnotherPlaylist = $db->orderby('RAND()')->getOne(T_PLAYLISTS);

if (!empty($getAnotherPlaylist)) {
	$final_data[] = [
		'title' => $getAnotherPlaylist->name,
		'thumbnail' => getMedia($getAnotherPlaylist->thumbnail),
		'url' => getLink('playlist/' . $getAnotherPlaylist->uid),
		'ajax_url' => 'playlist/' . $getAnotherPlaylist->uid
	];
}

if (IS_LOGGED) {
	$db->where("user_id NOT IN (SELECT user_id FROM blocks WHERE blocked_id = $user->id)");
}
$db->where('availability', 0);
if (!empty($getOneSong)) {
	$db->where('id', $getOneSong->id,'<>');
}


    if (!empty($interests)) {
        $db->where('category_id',array_keys($interests),'IN');
    }
$getAnotherSong = $db->orderby('RAND()')->getOne(T_SONGS);

if (!empty($getAnotherSong)) {
	$final_data[] = [
		'title' => $getAnotherSong->title,
		'thumbnail' => getMedia($getAnotherSong->thumbnail),
		'url' => getLink('track/' . $getAnotherSong->audio_id),
		'ajax_url' => 'track/' . $getAnotherSong->audio_id
	];
}

$top_slider_list = "";
if (!empty($final_data)) {

	foreach ($final_data as $key => $topList) {
		$top_slider_list .= loadPage("discover/top_slider_list", [
			'url' => $topList['url'],
			'title' => $topList['title'],
			'thumbnail' => $topList['thumbnail'],
			'ajax_url' => $topList['ajax_url'],
		]);
	}
}

if (!empty($_SESSION['fingerPrint'])) {
	$db->where('fingerprint', secure($_SESSION['fingerPrint']));
} else if (IS_LOGGED) {
	$db->where('user_id', secure($user->id));
}

$getRecentPlay = $db->groupBy('track_id')->orderBy('id', 'DESC')->get(T_VIEWS, 10);

$recent_plays = '';

if (!empty($getRecentPlay)) {
	foreach ($getRecentPlay as $key => $list) {
		$songData = songData($list->track_id);
		if (!empty($songData)) {
			$recent_plays .= loadPage("discover/recently-list", [
				'url' => $songData->url,
				'title' => $songData->title,
				'thumbnail' => $songData->thumbnail,
				'id' => $songData->id,
				'audio_id' => $songData->audio_id,
				'USER_DATA' => $songData->publisher
			]);
		}
	}
}

if (IS_LOGGED) {
	$db->where("user_id NOT IN (SELECT user_id FROM blocks WHERE blocked_id = $user->id)");
}
if (IS_LOGGED) {
    if (!empty($interests)) {
        $db->where('category_id',array_keys($interests),'IN');
    }
}
$getNewRelease = $db->where('availability', 0)->orderBy('id', 'DESC')->get(T_SONGS, 12);

$newReleases = '';

if (!empty($getNewRelease)) {
	foreach ($getNewRelease as $key => $song) {
		$songData = songData($song, false, false);
		$newReleases .= loadPage("discover/recently-list", [
			'url' => $songData->url,
			'title' => $songData->title,
			'thumbnail' => $songData->thumbnail,
			'id' => $songData->id,
			'audio_id' => $songData->audio_id,
			'USER_DATA' => $songData->publisher
		]);
	}
}

$time_week = time() - 604800;
$query = "SELECT " . T_SONGS . ".*, COUNT(" . T_VIEWS . ".id) AS " . T_VIEWS . "
FROM " . T_SONGS . " LEFT JOIN " . T_VIEWS . " ON " . T_SONGS . ".id = " . T_VIEWS . ".track_id
WHERE " . T_VIEWS . ".time > $time_week AND " . T_SONGS . ".availability = '0'";

if (IS_LOGGED) {
	$query .= " AND " . T_SONGS . ".user_id NOT IN (SELECT user_id FROM blocks WHERE blocked_id = $user->id)";
}

if (IS_LOGGED) {
    if (!empty($interests)) {
        //$query .= " AND category_id IN(" . implode(array_keys($interests)) . ") ";
    }
}

$limit_theme = 10;
if( $config['theme'] == 'volcano' ){
    $limit_theme = 5;
}
$query .= " GROUP BY " . T_SONGS . ".id
ORDER BY " . T_VIEWS . " DESC LIMIT ".$limit_theme;
$getMostWeek = $db->rawQuery($query);

$thisWeek = '';

if (!empty($getMostWeek)) {
	foreach ($getMostWeek as $key => $song) {
		$songData = songData($song, false, false);
		$thisWeek .= loadPage("discover/recommended-list", [
			'url' => $songData->url,
			'title' => $songData->title,
			'thumbnail' => $songData->thumbnail,
			'id' => $songData->id,
			'audio_id' => $songData->audio_id,
			'USER_DATA' => $songData->publisher,
			'key' => ($key + 1),
			'fav_button' => getFavButton($songData->id, 'fav-icon'),
			'duration' => $songData->duration
		]);
	}
}
$best_this_week = '';
$time = strtotime(date('l').", ".date('M')." ".date('d').", ".date('Y'));

if (date('l') == 'Saturday') {
	$week_start = strtotime(date('M')." ".date('d').", ".date('Y')." 12:00am");
}
else{
	$week_start = strtotime('last saturday, 12:00am', $time);
}

if (date('l') == 'Friday') {
	$week_end = strtotime(date('M')." ".date('d').", ".date('Y')." 11:59pm");
}
else{
	$week_end = strtotime('next Friday, 11:59pm', $time);
}
if ($music->config->store_system == 'on') {
	$getTopSongs = $db->rawQuery("SELECT track_id, COUNT(track_id) AS count FROM `".T_PURCHAES."` WHERE `time` <= '".$week_end."' AND `time` >= '".$week_start."' GROUP BY track_id ORDER BY count,`time` DESC LIMIT 10");
	$getTopProducts = $db->rawQuery("SELECT product_id, COUNT(product_id) AS count FROM `".T_ORDERS."` WHERE `time` <= '".$week_end."' AND `time` >= '".$week_start."' GROUP BY product_id ORDER BY count,`time` DESC LIMIT 10");
	$count = 1;
	if (!empty($getTopSongs)) {
		foreach ($getTopSongs as $key => $value) {
			$songData = songData($value->track_id);
			if (!empty($songData)) {
				$best_this_week .= loadPage("discover/best_sell", [
					'url' => $songData->url,
					'title' => $songData->title,
					'thumbnail' => $songData->thumbnail,
					'id' => $songData->id,
					'audio_id' => $songData->audio_id,
					'USER_DATA' => $songData->publisher,
					'data_load' => 'track/'.$songData->audio_id,
					'key' => $count,
					'fav_button' => getFavButton($songData->id, 'fav-icon'),
					'duration' => $songData->duration
				]);
				$count++;
			}
		}
	}
	if (!empty($getTopProducts)) {
		foreach ($getTopProducts as $key => $value) {
			$music->product = GetProduct($value->product_id);
			if (!empty($music->product)) {
				$best_this_week .= loadPage("discover/best_sell", [
					'url' => $music->product->url,
					'data_load' => $music->product->data_load,
					'title' => $music->product->title,
					'thumbnail' => $music->product->images[0]['image'],
					'id' => $music->product->id,
					'price' => $music->product->price,
					'USER_DATA' => $music->product->user_data,
					'key' => $count,
				]);
				unset($music->product);
				$count++;
			}
		}
	}
}
else{
	$getTopSongs = $db->rawQuery("SELECT track_id, COUNT(track_id) AS count FROM `".T_PURCHAES."` WHERE `time` <= '".$week_end."' AND `time` >= '".$week_start."' GROUP BY track_id ORDER BY count,`time` DESC LIMIT 10");
	if (!empty($getTopSongs)) {
		foreach ($getTopSongs as $key => $value) {
			$songData = songData($value->track_id);
			if (!empty($songData)) {
				$best_this_week .= loadPage("discover/best_sell", [
					'url' => $songData->url,
					'title' => $songData->title,
					'thumbnail' => $songData->thumbnail,
					'id' => $songData->id,
					'audio_id' => $songData->audio_id,
					'USER_DATA' => $songData->publisher,
					'data_load' => 'track/'.$songData->audio_id,
					'key' => ($key + 1),
					'fav_button' => getFavButton($songData->id, 'fav-icon'),
					'duration' => $songData->duration
				]);
			}
		}
	}
}

$recommended_html = '';
$recommended = GetRecommendedSongs();
foreach ($recommended as $key => $songData) {
    $recommended_html .= loadPage("discover/recommended-list", [
        'url' => $songData->url,
        'title' => $songData->title,
        'thumbnail' => $songData->thumbnail,
        'id' => $songData->id,
        'audio_id' => $songData->audio_id,
        'USER_DATA' => $songData->publisher,
        'key' => ($key + 1),
        'fav_button' => getFavButton($songData->id, 'fav-icon'),
        'duration' => $songData->duration
    ]);

}

$announcement_html = '';
/* Get active Announcements */
if (IS_LOGGED === true) {
    $announcement          = get_announcments();
    if(!empty($announcement)) {
        $announcement_html =  loadPage("announcements/content",array(
            'ANN_ID'       => $announcement->id,
            'ANN_TEXT'     => htmlspecialchars_decode(str_replace('<br>','',$announcement->text)),
        ));
    }
}
$music->announcement = $announcement_html;
/* Get active Announcements */

$music->site_title = lang('Discover');
$music->site_description = $music->config->description;
$music->site_pagename = "discover";
$music->site_content = loadPage("discover/content", [
	'TOP_SLIDER_CONTENT' => $top_slider_list,
	'RECENT_PLAYS' => $recent_plays,
	'NEW_RELEASES' => $newReleases,
	'MOST_THIS_WEEK' => $thisWeek,
	'BEST_THIS_WEEK' => $best_this_week,
    'MOST_RECOMMENDED' => $recommended_html,
    'ANNOUNCEMENT'     => $announcement_html
]);