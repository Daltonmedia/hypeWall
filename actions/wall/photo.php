<?php

$user = elgg_get_logged_in_user_entity();
$desc = get_input('description');
$location = get_input('location');
$tags = get_input('wall_tag_guids');
$attachment = get_input('attachment', null);
$wall_owner_guid = get_input('wall_owner', $user->guid);

$photo = get_entity($attachment);
if ($photo) {
	$photo->access_id = get_input('access_id');
	$photo->description = $desc;
	$photo->save();
}

$wall = new ElggObject();
$wall->subtype = 'hjwall';
$wall->access_id = get_input('access_id');
$wall->owner_guid = $user->guid;
$wall->container_guid = $user->guid;
$wall->title = '';
$wall->description = $desc;

if ($wall->save()) {

	add_entity_relationship($wall_owner_guid, 'wall_owner', $wall->guid);

	add_to_river('river/object/hjwall/create', 'create', $user->guid, $wall->guid);

	if ($tags) {
		foreach ($tags as $tag) {
			$tagged_user = get_entity($tag);
			add_entity_relationship($tagged_user->guid, 'tagged_in', $wall->guid);
			add_entity_relationship($tagged_user->guid, 'tagged_in', $photo->guid);

			$to = $tagged_user->guid;
			$from = $user->guid;
			$subject = elgg_echo('hj:wall:tagged:notification:subject', array($user->name));
			$message = elgg_echo('hj:wall:tagged:notification:message', array(
				$user->name,
				$wall->description,
				$wall->getURL()
			));

			notify_user($to, $from, $subject, $message);

			add_to_river('river/relationship/tagged', 'tagged', $tagged_user->guid, $wall->guid);
		}
	}

	$wall->location = $location;
	add_entity_relationship($wall->guid, 'wall_attachment', $attachment);
	$wall->attachment = $attachment;

//	$user->status = $wall->description . hj_wall_get_tags_str($wall);
	
	system_message(elgg_echo('hj:wall:create:success'));
	forward($wall->getURL());
} else {
	register_error(elgg_echo('hj:wall:create:error'));
	forward(REFERER);
}

