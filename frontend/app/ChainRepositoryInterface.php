<?php

namespace App;

interface ChainRepositoryInterface {

    public function get_chain_height();

    public function get_avg_difficulty_per_last_n_days(int $days = 7, string $tz = 'UTC');

    public function get_top_miners_in_last_24h(string $address, int $limit = 10, string $tz = 'UTC');

    public function get_miners_last_24h_summary(string $tz = 'UTC', int $limit = 300);

    public function get_sync_status(int $threshold = 10000): array;

    public function get_wallet_daily_gain(string $address, int $days = 10, string $tz = 'UTC');

    public function get_wallet_24h_gain(string $address, string $tz = 'UTC');

    public function get_wallet_compute_power(string $address, int $days = 10, string $tz = 'UTC');

    public function get_wallet_hourly_gain(string $address, int $hours = 48, string $tz = 'UTC');

    public function get_hourly_difficulty(int $hours = 48, string $tz = 'UTC');

}
