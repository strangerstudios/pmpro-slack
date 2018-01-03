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

//returns true if a given slack user id is a member of the channel with the given channel_id, false otherwise
function pmprosla_slack_user_in_channel($slack_user_id, $channel_id){
	$response = file_get_contents('https://slack.com/api/channels.info?channel='.$channel_id.'&token='.pmprosla_get_oauth());
	$response_arr = json_decode($response, true);
	if($response_arr['ok']) {
		return in_array($slack_user_id, $response_arr['channel']['members']);
	}
	echo "Something went wrong: ".$response;
}


function pmprosla_switch_slack_channels_by_level($slack_user_id, $new_level_id = NULL, $old_level_id = NULL){
	$options = get_option( 'pmprosla_data' );
	
	//get arrays for old channels and new channels
	
	//remove all common channels between the two arrays
	
	//remove user from all channels still related to old level
	
	//add user to all channels still related to new level
	
	
}

//returns true if the user is successfully invited to the channel, false otherwise
function pmprosla_add_user_to_channel($slack_user_id, $channel_id){
	$response = file_get_contents('https://slack.com/api/channels.invite?channel='.$channel_id.'&user='.$slack_user_id.'&token='.pmprosla_get_oauth());
	$response_arr = json_decode($response, true);
	if($response_arr['ok']) {
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

function pmprosla_get_oauth(){
	$options = get_option( 'pmprosla_data' );
	if(!empty($options['oauth'])) {
		return $options['oauth'];
	}
}

?>