<?php
/*
 *	Slack API Calls
 */
 
//returns true if the email inputted is associated with a Slack user in the workspace, false otherwise
function pmprosla_email_in_slack_workspace($email){
	$response = file_get_contents('https://slack.com/api/users.lookupByEmail?email='.$email.'&token='.pmprosla_get_oauth());
	$response_arr = json_decode($response, true);
	return $response_arr['ok'];
}

//returns the user id associated with the acount with the given email in the Slack workspace, NULL otherwise
function pmprosla_get_slack_user_id($email){
	$response = file_get_contents('https://slack.com/api/users.lookupByEmail?email='.$email.'&token='.pmprosla_get_oauth());
	$response_arr = json_decode($response, true);
	if($response_arr['ok']) {
		return $response_arr['user']['id'];
	}
	echo "Something went wrong: ".$response;
}

//returns the channel id associated with the channel with the inputted name in the Slack workspace, NULL otherwise
function pmprosla_get_slack_channel_id($channel_name){
	$response = file_get_contents('https://slack.com/api/channels.list?exclude_members=true&token='.pmprosla_get_oauth());
	$response_arr = json_decode($response, true);
	if($response_arr['ok']) {
		foreach($response_arr['channels'] as $channel_info) {
			if($channel_info['name_normalized'] == trim($channel_name)) {
				return $channel_info['id'];
			}
		}
		return;
	}
	echo "Something went wrong: ".$response;
}

//returns the channel name associated with the channel with the inputted id in the Slack workspace, NULL otherwise
function pmprosla_get_slack_channel_name($channel_id){
	$response = file_get_contents('https://slack.com/api/channels.info?channel='.$channel_id.'&token='.pmprosla_get_oauth());
	$response_arr = json_decode($response, true);
	if($response_arr['ok']) {
		return $response_arr['channel']['name'];
	}
	echo "Something went wrong: ".$response;
}

//returns true if a given slack user id is a member of the channel with the given channel_id, false otherwise
function pmprosla_slack_user_in_channel($slack_user_id, $channel_id){
	$response = file_get_contents('https://slack.com/api/channels.info?channel='.$channel_id.'&token='.pmprosla_get_oauth());
	$response_arr = json_decode($response, true);
	if($response_arr['ok']) {
		return in_array($slack_user_id, $response_arr['channel']['members']);
	}
	echo "Something went wrong: ".$response;
}

//TODO: Maybe take array of levels to cancel, if just an int change into an array containing an int
function pmprosla_switch_slack_channels_by_level($slack_user_id, $new_level_id = NULL, $old_level_ids = NULL){
	$options = get_option( 'pmprosla_data' );
	
	//get arrays for old channels and new channels
	$new_level_channels = [];
	if(!empty($new_level_id)){
		$new_level_channels = $options['channel_add_settings'][$new_level_id.'_channels'];
	}
	$old_level_channels = [];
	if(!empty($old_level_ids)){
		foreach($old_level_ids as $old_level_id){
			if(!empty($options['channel_add_settings'][$old_level_id.'_enabled'])&&$options['channel_add_settings'][$old_level_id.'_enabled']==true){
				foreach($options['channel_add_settings'][$old_level_id.'_channels'] as $channel_id){
					$old_level_channels[] = $channel_id;
				}
			}
		}
	}
	
	if(empty($options['channel_add_settings'][$new_level_id.'_enabled'])||$options['channel_add_settings'][$new_level_id.'_enabled']==false){
		$new_level_channels = [];
	}
	
	//remove all common channels between the two arrays
	$channels_to_add = array_diff($new_level_channels, $old_level_channels);
	$channels_to_remove = array_diff($old_level_channels, $new_level_channels);
	
	//remove user from all channels still related to old level
	foreach($channels_to_remove as $channel) {
		pmprosla_remove_user_from_channel($slack_user_id, $channel);
	}
	
	//add user to all channels still related to new level
	foreach($channels_to_add as $channel) {
		pmprosla_add_user_to_channel($slack_user_id, $channel);
	}
	
}

//returns true if the user is successfully invited to the channel, false otherwise
function pmprosla_add_user_to_channel($slack_user_id, $channel_id){
	$response = file_get_contents('https://slack.com/api/channels.invite?channel='.$channel_id.'&user='.$slack_user_id.'&token='.pmprosla_get_oauth());
	$response_arr = json_decode($response, true);
	if($response_arr['ok']) {
		return true;
	}
	if($response_arr['error']=="already_in_channel"){
		return true;
	}
	echo "Something went wrong: ".$response;
	return false;
}

//returns true if the user is successfully kicked from the channel, false otherwise
function pmprosla_remove_user_from_channel($slack_user_id, $channel_id){
	$response = file_get_contents('https://slack.com/api/channels.kick?channel='.$channel_id.'&user='.$slack_user_id.'&token='.pmprosla_get_oauth());
	$response_arr = json_decode($response, true);
	if($response_arr['ok']) {
		return true;
	}
	echo "Something went wrong: ".$response;
	return false;
}

function pmprosla_invite_user_to_workspace($user, $level=NULL){
	$email = $user->user_email;
	// Could set first and last names automoatically, not sure if this is a good idea
	//$first_name = $user->first_name;
	//$last_name = $user->last_name;
	$options = get_option( 'pmprosla_data' );
	$channels = "";
	foreach($options['channel_add_settings'][$level.'_channels'] as $channel) {
		$channels = $channels . $channel . ',';
	}
	$channels = substr($channels, 0, -1);
	$response = file_get_contents('https://slack.com/api/users.admin.invite?'
		.'email='.$email
		.'&channels='.$channels
		.'&resend=true'
		.'&token='.pmprosla_get_oauth());
	$response_arr = json_decode($response, true);
	if($response_arr['ok']) {
		return true;
	}
	echo "Something went wrong: ".$response;
	return false;
}

function pmprosla_get_oauth(){
	$options = get_option( 'pmprosla_data' );
	if(!empty($options['oauth'])) {
		return $options['oauth'];
	}
}

?>