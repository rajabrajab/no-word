<?php

/*
|--------------------------------------------------------------------------
| API response messages
|--------------------------------------------------------------------------
|
| Every message the JSON API puts in the `message` key of its envelope. The
| response macros run whatever they are handed through the translator, so
| controllers and services pass one of these keys, never a sentence.
|
| Each key added here needs the same key in lang/ar/api.php.
|
*/

return [

    'index_success' => 'Data retrieved successfully.',
    'show_success' => 'Data retrieved successfully.',
    'create_success' => 'Record created successfully.',
    'store_success' => 'Data stored successfully.',
    'edit_success' => 'Edit operation completed successfully.',
    'update_success' => 'Data updated successfully.',
    'delete_success' => 'Record deleted successfully.',
    'general_success' => 'Operation completed successfully.',
    'general_failure' => 'Operation failed. Please try again later.',
    'not_found' => 'Not found.',
    'validation_failure' => 'Validation failed.',
    'unauthorized' => 'Email or password is incorrect.',
    'session_expired' => 'Your session has expired. Please sign in again.',

    'auth' => [
        'logged_in' => 'Logged in successfully.',
        'logged_out' => 'Logged out successfully.',
        'account_deleted' => 'Account deleted successfully.',
        'user_not_found' => 'No account was found for this email.',
        'password_updated' => 'Password updated successfully.',
        'token_refreshed' => 'Token refreshed successfully.',
        'otp_sent' => 'A verification code has been sent to your email.',
        'otp_sent_again' => 'A new verification code has been sent to your email.',
        'otp_already_sent' => 'A verification code has already been sent. Please check your email.',
        'otp_confirmed' => 'Verification code confirmed.',
        'otp_invalid' => 'The verification code is incorrect.',
        'otp_expired' => 'The verification code is incorrect or has expired.',
        'otp_send_failed' => 'The verification code could not be sent. Please try again.',
        'otp_wait' => 'Please wait :seconds seconds before asking for another code.',
        'temporary_key_invalid' => 'This link is no longer valid. Please start again.',
        'registration_data_missing' => 'No sign-up was started for this email.',
        'registered' => 'Your account was created successfully.',
    ],

    'game' => [
        'created' => 'Game created successfully.',
        'board_retrieved' => 'Game board retrieved successfully.',
        'reset' => 'Game reset successfully.',
        'not_found' => 'Game not found.',
        'unauthorized' => 'You do not have access to this game.',
        'team_unauthorized' => 'You do not have access to this team.',
        'team_not_in_game' => 'This team is not part of this game.',
        'question_answered' => 'Question marked as answered successfully.',
        'question_not_in_game' => 'This question is not part of this game.',
        'question_replaced' => 'Question replaced successfully.',
        'no_alternative_question' => 'There is no other question in the same category and level to swap in.',
        'replace_failed' => 'The question could not be replaced. Please try again.',
        'reset_failed' => 'The game could not be reset. Please try again.',
        'create_failed' => 'The game could not be created. Please try again.',
        'score_updated' => 'Team score updated successfully.',
        'helping_method_used' => 'Helping method used successfully.',
        'helping_method_already_used' => 'This helping method has already been used.',
        'helping_method_needs_question' => 'Choose the question you want to use this helping method on.',
        'no_remaining_games' => 'You have no games left in your subscription. Please subscribe to a package.',
        'my_games_retrieved' => 'Your games were retrieved successfully.',
    ],

    'tournament' => [
        'created' => 'Tournament created successfully.',
        'create_failed' => 'The tournament could not be created. Please try again.',
        'unauthorized' => 'You do not have access to this tournament.',
        'game_created' => 'Game created successfully.',
        'game_linked' => 'Game linked successfully.',
        'game_create_failed' => 'The tournament game could not be created. Please try again.',
        'winner_set' => 'Winner set successfully.',
        'winner_set_failed' => 'The winner could not be set. Please try again.',
        'winner_not_in_match' => 'The winner must be one of the two teams in this match.',
        'match_not_in_tournament' => 'This match does not belong to this tournament.',
        'match_not_pending' => 'This match has already been played.',
        'match_not_ready' => 'This match is not ready yet. Both teams must be set first.',
        'match_already_linked' => 'This match already has a game linked to it.',
        'teams_not_in_tournament' => 'Both teams must belong to this tournament.',
        'exactly_two_teams' => 'Exactly two teams are required.',
        'invalid_size' => 'A tournament must have 4, 8 or 16 teams.',
    ],

    'package' => [
        'subscribed' => 'Subscription completed successfully.',
        'subscribe_failed' => 'The subscription could not be completed. Please try again.',
        'cancelled' => 'Subscription cancelled successfully.',
        'already_cancelled' => 'This subscription has already been cancelled.',
        'cancel_unauthorized' => 'You do not have permission to cancel this subscription.',
        'coupon_applied' => 'Coupon applied successfully.',
        'coupon_apply_failed' => 'The coupon could not be applied. Please try again.',
        'coupon_inactive' => 'This coupon is not active.',
        'coupon_expired' => 'This coupon has expired.',
        'coupon_exhausted' => 'This coupon has no uses left.',
    ],

    'validation' => [
        'categories_max' => 'You may choose up to 6 categories.',
        'teams_size' => 'Exactly two teams are required.',
        'phone_invalid' => 'This phone number is not valid or not supported.',
    ],

];
