<?php
require "../vendor/autoload.php";

use Amp\Loop;
use EnjinCoin\Util\Db;
use EnjinCoin\Config;
use EnjinCoin\Prices;

function onRepeat($watcherID) {
	$db = Db::getInstance();
	$timestamp = floor(time() / 60) * 60;
	$value = [];

	$markets = Config::get()->prices->markets;
	foreach($markets as $market_title => $options) {
		// make sure the exchange supports this market
		$model_prices = new Prices(strtolower($options->exchange));
		$exchange_markets = $model_prices->fetchMarkets();
		$found = false;
		foreach($exchange_markets as $market) {
			$symbols[] = $market['symbol'];
			if ($market['symbol'] == $market_title) {
				$found = true;
				break;
			}
		}

		if (!$found) {
			continue;
		}

		// get the exchange pricing data for the market
		$ticker = $model_prices->fetchTicker($market_title);
		if (!empty($ticker['last'])) {
			$value[$market_title] = number_format($ticker['last'], 8);
		}

		// update pusher channels
		if ($options->push_notifications) {

		}
	}

	try {
		// try inserting first
		$insert = $db->insert('prices');
		$insert->values(array(
			'timestamp' => $timestamp,
			'value' => json_encode($value),
		), $insert::VALUES_MERGE);
		Db::query($insert);
	} catch (Exception $e) {
		// update if row (minute) already exists
		$update = $db->update('prices');
		$update->where(['timestamp' => $timestamp]);
		$update->set(['value' => json_encode($value)]);
		Db::query($update);
	}
}

function prune($watcherID) {
	// clear rows older than the set amount of maximum days
	$max_days = Config::get()->prices->max_days;
	$exp_time = strtotime("{$max_days} days ago");
	$db = Db::getInstance();
	$delete = $db->delete('prices');
	$delete->where(['timestamp < ?' => $exp_time]);
	Db::query($delete);
}

Loop::run(function () {
	Loop::repeat(15000, "onRepeat");
	Loop::repeat(43200000, "prune");
});
