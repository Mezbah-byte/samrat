<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if ( ! function_exists('setting'))
{
	/** Read a value from the settings table (cached per request). */
	function setting($key, $default = NULL)
	{
		$CI =& get_instance();
		if ( ! isset($CI->setting_model))
		{
			$CI->load->model('setting_model');
		}
		return $CI->setting_model->get($key, $default);
	}
}

if ( ! function_exists('currency'))
{
	function currency()
	{
		return setting('currency_symbol', '$');
	}
}

if ( ! function_exists('money'))
{
	/** Display helper: `$1,234.56`. Storage stays at 8 decimals. */
	function money($amount, $with_symbol = TRUE, $decimals = MONEY_DISPLAY)
	{
		$formatted = number_format((float) $amount, $decimals, '.', ',');
		return $with_symbol ? currency().$formatted : $formatted;
	}
}

if ( ! function_exists('money_raw'))
{
	/** Normalise a value to the storage scale before it reaches the DB. */
	function money_raw($amount)
	{
		return number_format((float) $amount, MONEY_SCALE, '.', '');
	}
}

if ( ! function_exists('percent'))
{
	function percent($value)
	{
		return rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.').'%';
	}
}

if ( ! function_exists('upload_url'))
{
	/** URL for a stored upload, or a placeholder when the file is missing. */
	function upload_url($folder, $file, $fallback = NULL)
	{
		if ($file && is_file(UPLOAD_PATH.$folder.DIRECTORY_SEPARATOR.$file))
		{
			return base_url('uploads/'.$folder.'/'.rawurlencode($file));
		}
		return $fallback ? base_url($fallback) : base_url('assets/img/placeholder.svg');
	}
}

if ( ! function_exists('avatar_url'))
{
	function avatar_url($file)
	{
		return upload_url('avatars', $file, 'assets/img/avatar.svg');
	}
}

if ( ! function_exists('logo_url'))
{
	function logo_url()
	{
		$logo = setting('logo');
		return $logo ? upload_url('logo', $logo, 'assets/img/logo.svg') : base_url('assets/img/logo.svg');
	}
}

if ( ! function_exists('badge'))
{
	/** Bootstrap badge markup for the status enums used across the app. */
	function badge($status)
	{
		$map = array(
			'active'    => 'success', 'completed' => 'primary',  'inactive'  => 'secondary',
			'pending'   => 'warning', 'approved'  => 'info',     'paid'      => 'success',
			'rejected'  => 'danger',  'blocked'   => 'danger',   'cancelled' => 'secondary',
			'credited'  => 'success', 'missed'    => 'danger',   'published' => 'success',
			'draft'     => 'secondary',
		);
		$class = isset($map[$status]) ? $map[$status] : 'secondary';
		return '<span class="badge text-bg-'.$class.'">'.ucfirst(str_replace('_', ' ', $status)).'</span>';
	}
}

if ( ! function_exists('chip'))
{
	/** Pill badge for the same status enums, styled by assets/css/ui.css. */
	function chip($status)
	{
		$map = array(
			'active'    => 'ok',   'completed' => 'info', 'inactive'  => 'mute',
			'pending'   => 'warn', 'approved'  => 'info', 'paid'      => 'ok',
			'rejected'  => 'bad',  'blocked'   => 'bad',  'cancelled' => 'mute',
			'credited'  => 'ok',   'missed'    => 'bad',  'published' => 'ok',
			'draft'     => 'mute',
		);
		$tone = isset($map[$status]) ? $map[$status] : 'mute';
		return '<span class="chip chip-'.$tone.'">'.ucfirst(str_replace('_', ' ', $status)).'</span>';
	}
}

if ( ! function_exists('tx_label'))
{
	function tx_label($type)
	{
		$map = array(
			'deposit'        => 'Deposit',
			'investment'     => 'Package Purchase',
			'daily_profit'   => 'Daily Profit',
			'referral_bonus' => 'Referral Bonus',
			'withdrawal'     => 'Withdrawal',
			'withdrawal_fee' => 'Withdrawal Fee',
			'refund'         => 'Refund',
			'admin_credit'   => 'Admin Credit',
			'admin_debit'    => 'Admin Debit',
		);
		return isset($map[$type]) ? $map[$type] : ucfirst(str_replace('_', ' ', $type));
	}
}

if ( ! function_exists('flash'))
{
	/** Renders and consumes success/error/warning/info flashdata. */
	function flash()
	{
		$CI  =& get_instance();
		$out = '';
		// The data-flash tone is what assets/js/ui.js reads to lift the message
		// into a toast; without JS the Bootstrap alert stands on its own.
		$tones = array('success' => 'ok', 'danger' => 'bad', 'warning' => 'warn', 'info' => 'info');
		foreach (array('success' => 'success', 'error' => 'danger', 'warning' => 'warning', 'info' => 'info') as $key => $class)
		{
			if ($msg = $CI->session->flashdata($key))
			{
				$out .= '<div class="alert alert-'.$class.' alert-dismissible fade show" role="alert" data-flash="'.$tones[$class].'">'
					.html_escape($msg)
					.'<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
			}
		}
		return $out;
	}
}

if ( ! function_exists('fmt_date'))
{
	function fmt_date($datetime, $format = 'd M Y, h:i A')
	{
		if (empty($datetime) || $datetime === '0000-00-00 00:00:00')
		{
			return '-';
		}
		return date($format, strtotime($datetime));
	}
}

if ( ! function_exists('short_txt'))
{
	/** Middle-truncate long hashes and wallet addresses. */
	function short_txt($text, $head = 10, $tail = 6)
	{
		$text = (string) $text;
		if (strlen($text) <= $head + $tail + 3)
		{
			return $text;
		}
		return substr($text, 0, $head).'...'.substr($text, -$tail);
	}
}

if ( ! function_exists('active_if'))
{
	function active_if($current, $name, $class = 'active')
	{
		return $current === $name ? $class : '';
	}
}

if ( ! function_exists('country_list'))
{
	function country_list()
	{
		return array(
			'Afghanistan','Albania','Algeria','Argentina','Armenia','Australia','Austria','Azerbaijan',
			'Bahrain','Bangladesh','Belarus','Belgium','Bhutan','Bolivia','Brazil','Bulgaria','Cambodia',
			'Canada','Chile','China','Colombia','Croatia','Cyprus','Czechia','Denmark','Ecuador','Egypt',
			'Estonia','Ethiopia','Finland','France','Georgia','Germany','Ghana','Greece','Hong Kong',
			'Hungary','Iceland','India','Indonesia','Iraq','Ireland','Israel','Italy','Japan','Jordan',
			'Kazakhstan','Kenya','Kuwait','Kyrgyzstan','Laos','Latvia','Lebanon','Libya','Lithuania',
			'Luxembourg','Malaysia','Maldives','Malta','Mexico','Moldova','Mongolia','Morocco','Myanmar',
			'Nepal','Netherlands','New Zealand','Nigeria','North Macedonia','Norway','Oman','Pakistan',
			'Palestine','Panama','Peru','Philippines','Poland','Portugal','Qatar','Romania','Russia',
			'Saudi Arabia','Senegal','Serbia','Singapore','Slovakia','Slovenia','Somalia','South Africa',
			'South Korea','Spain','Sri Lanka','Sudan','Sweden','Switzerland','Syria','Taiwan','Tajikistan',
			'Tanzania','Thailand','Tunisia','Turkey','Turkmenistan','Uganda','Ukraine','United Arab Emirates',
			'United Kingdom','United States','Uruguay','Uzbekistan','Venezuela','Vietnam','Yemen','Zambia','Zimbabwe',
		);
	}
}

if ( ! function_exists('network_list'))
{
	function network_list()
	{
		return array('TRC20' => 'USDT TRC20 (TRON)', 'BEP20' => 'USDT BEP20 (BNB Chain)', 'ERC20' => 'USDT ERC20 (Ethereum)');
	}
}

if ( ! function_exists('pager'))
{
	/**
	 * Minimal Bootstrap pager. $base_url must already carry any filter query
	 * string; the page number is appended as `page=N`.
	 */
	function pager($base_url, $total, $per_page, $current)
	{
		$pages = (int) ceil($total / max(1, $per_page));
		if ($pages < 2)
		{
			return '';
		}

		$glue    = (strpos($base_url, '?') === FALSE) ? '?' : '&';
		$current = max(1, min($pages, (int) $current));
		$start   = max(1, $current - 2);
		$end     = min($pages, $start + 4);
		$start   = max(1, $end - 4);

		$html = '<nav><ul class="pagination pagination-sm mb-0">';
		$html .= '<li class="page-item'.($current <= 1 ? ' disabled' : '').'">'
			.'<a class="page-link" href="'.$base_url.$glue.'page='.($current - 1).'">&laquo;</a></li>';
		for ($i = $start; $i <= $end; $i++)
		{
			$html .= '<li class="page-item'.($i === $current ? ' active' : '').'">'
				.'<a class="page-link" href="'.$base_url.$glue.'page='.$i.'">'.$i.'</a></li>';
		}
		$html .= '<li class="page-item'.($current >= $pages ? ' disabled' : '').'">'
			.'<a class="page-link" href="'.$base_url.$glue.'page='.($current + 1).'">&raquo;</a></li>';
		$html .= '</ul></nav>';

		return $html;
	}
}
