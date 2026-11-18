<?php
/* vim: set noexpandtab tabstop=2 softtabstop=2 shiftwidth=2: */

/**
 * Cosine's php8 Chat Extensions
 *
 * This plugin adds several chat commands and replaces plugin.rasp_chat.php.
 * There are several commands, categorized as follows:
 * 
 *   Targeted:      /hug /highfive /slap /poke           <login>        (target required)
 *   Optional:      /n1 /gg /thanks /wave                [login]        (to a player, or to everyone)
 *   Emotes:        /dance /shrug /facepalm
 *   Games:         /roll [max]   /flip   /8ball <question>
 *
 * Dependencies: XASECO with PHP 8 compatibility (get @ https://codeberg.org/hanouta/xaseco)
 */

global $cce_cooldown, $cce_lastused, $cce_social, $cce_8ball;

$cce_cooldown = 3; // command cooldown
$cce_lastused = array();

// name => array(mode, text with target, text without target, help)
//   mode: 'must' = needs a target, 'opt' = target optional, 'none' = no target
//   {1} = your in-game nickname, {2} = target's in-game nickname
$cce_social = array(
	'hug'       => array('must', '{1} hugs {2} !',                       '',                               'Hug another player: /hug <login>'),
	'highfive'  => array('must', '{1} high-fives {2} !',                 '',                               'High-five another player: /highfive <login>'),
	'slap'      => array('must', '{1} slaps {2} with a large trout !',   '',                               'Slap a player with a trout: /slap <login>'),
	'poke'      => array('must', '{1} pokes {2} .',                      '',                               'Poke another player: /poke <login>'),
	'n1'        => array('opt',  '{1} says nice one to {2} !',           '{1} says nice one !',            'Say nice one: /n1 [login]'),
	'gg'        => array('opt',  '{1} says good game to {2} !',          '{1} says good game to everyone !', 'Say good game: /gg [login]'),
	'thanks'    => array('opt',  '{1} thanks {2} !',                     '{1} says thanks to everyone !',  'Say thanks: /thanks [login]'),
	'wave'      => array('opt',  '{1} waves at {2} .',                   '{1} waves at everyone .',        'Wave at someone: /wave [login]'),
	'dance'     => array('none', '',                                     '{1} dances around !',            'Do a little dance: /dance'),
	'shrug'     => array('none', '',                                     '{1} shrugs .',                   'Shrug: /shrug'),
	'facepalm'  => array('none', '',                                     '{1} facepalms .',                'Facepalm: /facepalm'),
);

$cce_8ball = array(
	'It is certain.', 'Without a doubt.', 'Yes, definitely.', 'Most likely.',
	'Signs point to yes.', 'Ask again later.', 'Cannot predict now.',
	'Don\'t count on it.', 'My sources say no.', 'Very doubtful.',
);

foreach ($cce_social as $cce_name => $cce_def) {
	Aseco::addChatCommand($cce_name, $cce_def[3]);
}
Aseco::addChatCommand('roll', 'Roll a die: /roll [max], default 100');
Aseco::addChatCommand('flip', 'Flip a coin: /flip');
Aseco::addChatCommand('8ball', 'Ask the magic 8-ball: /8ball <question>');

// ---------------------------------------------------------------- helpers

function cce_tell($aseco, $login, $text) {
	$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors($text), $login);
}

function cce_say($aseco, $text) {
	$aseco->client->query('ChatSendServerMessage', $aseco->formatColors($text));
}

function cce_allowed($aseco, $player, $cmdname) {
	global $cce_cooldown, $cce_lastused;

	if (in_array($player->login, $aseco->server->mutelist)) {
		$message = formatText($aseco->getChatMessage('MUTED'), '/' . $cmdname);
		cce_tell($aseco, $player->login, $message);
		return false;
	}

	$now = time();
	if (isset($cce_lastused[$player->login]) && $now - $cce_lastused[$player->login] < $cce_cooldown) {
		cce_tell($aseco, $player->login, '{#server}> {#error}Easy there! Wait a moment before using another command.');
		return false;
	}
	$cce_lastused[$player->login] = $now;
	return true;
}

function cce_nick($player) {
	return '{#highlite}' . $player->nickname . '$z$s{#server}';
}

function cce_social($aseco, $command, $name) {
	global $cce_social;

	$player = $command['author'];
	list($mode, $with, $without, $help) = $cce_social[$name];

	if (!cce_allowed($aseco, $player, $name)) return;

	$params = trim($command['params']);
	$target = false;

	$freetext = '';
	if ($mode == 'must' && $params != '') {
		// find (online) target; getPlayerParam() reports "not found" itself
		if (!$target = $aseco->getPlayerParam($player, $params)) return;
	} elseif ($mode == 'opt' && $params != '') {
		$target = $aseco->server->players->getPlayer($params);
		if (!$target) {
			$freetext = str_replace('$', '', stripColors($params, false));
			$freetext = (function_exists('mb_substr') ? mb_substr($freetext, 0, 60, 'UTF-8') : substr($freetext, 0, 60));
		}
	} elseif ($mode == 'must') {
		cce_tell($aseco, $player->login, '{#server}> {#error}Usage: {#highlite}$i ' . $help);
		return;
	}

	if ($target && $target->login == $player->login) {
		cce_tell($aseco, $player->login, '{#server}> {#error}You can\'t do that to yourself!');
		return;
	}

	if ($target)
		$text = formatText($with, cce_nick($player), cce_nick($target));
	elseif ($freetext != '')
		$text = formatText($with, cce_nick($player), '{#highlite}' . $freetext . '{#server}');
	else
		$text = formatText($without, cce_nick($player), '');
	$message = '{#server}> ' . $text;
	cce_say($aseco, $message);
}

// ---------------------------------------------------------------- games

function chat_roll($aseco, $command) {
	$player = $command['author'];
	if (!cce_allowed($aseco, $player, 'roll')) return;

	$max = 100;
	$params = trim($command['params']);
	if ($params != '') {
		if (!ctype_digit($params) || (int)$params < 2) {
			cce_tell($aseco, $player->login, '{#server}> {#error}Usage: {#highlite}$i /roll [max]  (a number of 2 or more)');
			return;
		}
		$max = min((int)$params, 1000000);
	}
	$message = '{#server}> ' . cce_nick($player) . ' rolls {#highlite}' . mt_rand(1, $max) . '{#server} (1-' . $max . ')';
	cce_say($aseco, $message);
}

function chat_flip($aseco, $command) {
	$player = $command['author'];
	if (!cce_allowed($aseco, $player, 'flip')) return;

	$message = '{#server}> ' . cce_nick($player) . ' flips a coin: {#highlite}' . (mt_rand(0, 1) ? 'Heads' : 'Tails');
	cce_say($aseco, $message);
}

function chat_8ball($aseco, $command) {
	global $cce_8ball;

	$player = $command['author'];
	if (!cce_allowed($aseco, $player, '8ball')) return;

	$question = trim($command['params']);
	if ($question == '') {
		cce_tell($aseco, $player->login, '{#server}> {#error}Usage: {#highlite}$i /8ball <question>');
		return;
	}

	$question = str_replace('$', '', stripColors($question, false));
	if (function_exists('mb_substr'))
		$question = mb_substr($question, 0, 100, 'UTF-8');
	else
		$question = substr($question, 0, 100);

	$message = '{#server}> ' . cce_nick($player) . ' asks: {#highlite}' . $question .
	           '{#server}  The 8-ball says: {#highlite}' . $cce_8ball[array_rand($cce_8ball)];
	cce_say($aseco, $message);
}

// ---------------------------------------------------------------- wrappers

function chat_hug($aseco, $command)      { cce_social($aseco, $command, 'hug'); }
function chat_highfive($aseco, $command) { cce_social($aseco, $command, 'highfive'); }
function chat_slap($aseco, $command)     { cce_social($aseco, $command, 'slap'); }
function chat_poke($aseco, $command)     { cce_social($aseco, $command, 'poke'); }
function chat_n1($aseco, $command)       { cce_social($aseco, $command, 'n1'); }
function chat_gg($aseco, $command)       { cce_social($aseco, $command, 'gg'); }
function chat_thanks($aseco, $command)   { cce_social($aseco, $command, 'thanks'); }
function chat_wave($aseco, $command)     { cce_social($aseco, $command, 'wave'); }
function chat_dance($aseco, $command)    { cce_social($aseco, $command, 'dance'); }
function chat_shrug($aseco, $command)    { cce_social($aseco, $command, 'shrug'); }
function chat_facepalm($aseco, $command) { cce_social($aseco, $command, 'facepalm'); }
?>
