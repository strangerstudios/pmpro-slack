<?php
/*
 *	Slack API Calls
 */
function pmprosla_email_in_slack_workspace($email){
	$response = file_get_contents('https://slack.com/api/users.lookupByEmail?email='.$email.'&token='.pmprosla_get_oauth());
	$response_arr = json_decode($response, true);
	return $response_arr['ok'];
}

function pmprosla_get_slack_user_id($email){
	$response = file_get_contents('https://slack.com/api/users.lookupByEmail?email='.$email.'&token='.pmprosla_get_oauth());
	$response_arr = json_decode($response, true);
	if($response_arr['ok']) {
		return $response_arr['user']['id'];
	}
	echo "Something went wrong: ".$response;
}

//fix
function pmprosla_get_slack_channel_id($channel_name){
	$response = file_get_contents('https://slack.com/api/channles.list?token='.pmprosla_get_oauth());
	$response_arr = json_decode($response, true);
	if($response_arr['ok']) {
		return $response_arr['channel']['id'];
	}
	echo "Something went wrong: ".$response;
}

function pmprosla_slack_user_in_channel($slack_user_id, $channel_id){
	
}

function pmprosla_switch_slack_channels_by_level($slack_user_id, $old_level_id, $new_level_id){

}

function pmprosla_add_user_to_channel($slack_user_id, $channel_id){

}

function pmprosla_remove_user_from_channel($slack_user_id, $channel_id){

}

function pmprosla_get_oauth(){
	$options = get_option( 'pmprosla_data' );
	if(!empty($options['oauth'])) {
		return $options['oauth'];
	}
}

?>