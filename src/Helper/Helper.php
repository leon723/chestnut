<?php

use Chestnut\Support\Container;

if (!function_exists('app')) {
	function app($component = null, $parameters = []) {
		if (is_null($component)) {
			return Container::getInstance();
		}

		return Container::getInstance()->make($component, $parameters);
	}
}

if (!function_exists('config')) {
	function config($key = null, $default = null) {
		if (!is_null($key)) {
			return app('config')->get($key, $default);
		} else {
			return app('config');
		}
	}
}

if (!function_exists('request')) {
	function request($key = null, $default = null) {
		if (is_null($key)) {
			return app('request');
		}

		return app('request')->get($key, $default);
	}
}

if (!function_exists('url')) {
	function url() {
		return call_user_func_array([app('route'), 'url'], func_get_args());
	}
}

if (!function_exists('view')) {
	function view($filename, $data = []) {
		if (is_null($filename)) {
			return app('view');
		}

		return app('view')->make($filename, $data);
	}
}

if (!function_exists('cookie')) {
	function cookie($name, $value = null, $expire = 0, $path = '/', $secure = false, $httpOnly = true) {
		if (is_null($value) && $cookie = app('request')->cookies->get($name)) {
			return decrypt($cookie);
		}

		if (!app('request')->cookies->has($name)) {
			$value = encrypt($value);

			if (is_numeric($expire) && $expire > 0) {
				$expire = time() + $expire;
			}

			$cookie = app('cookie', [
				'name' => $name,
				'value' => $value,
				'expire' => $expire,
				'path' => $path,
				'domain' => config('app.domain', 'chestnut.app'),
				'secure' => $secure,
				'httpOnly' => $httpOnly,
			]);

			app('response')->headers->setCookie($cookie);
		}
	}
}

if (!function_exists('cookie_remove')) {
	function cookie_remove($name) {
		if (app('request')->cookies->has($name)) {
			app('request')->cookies->remove($name);
		}
	}
}

if (!function_exists('session')) {
	function session($name = null, $value = null) {
		if (is_null($name)) {
			return app('session');
		}

		if (is_null($value)) {
			return app('session')->get($name);
		}

		app('session')->set($name, $value);
	}
}

if (!function_exists('session_flash')) {
	function session_flash($name = null, $value = null) {
		if (is_null($name)) {
			return app('session')->getFlashBag();
		}

		if (is_null($value)) {
			return app('session')->getFlashBag()->get($name, []);
		}

		app('session')->getFlashBag()->add($name, $value);
	}
}

if (!function_exists('encrypt')) {
	function encrypt($string) {
		$key = config('app.app_key');
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

		$cipher = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $string, MCRYPT_MODE_CBC, $iv);
		$cipher = 'Chestnut' . $cipher . $iv;

		$cipher_base64 = base64_encode($cipher);

		return trim($cipher_base64);
	}
}

if (!function_exists('decrypt')) {
	function decrypt($encrypt) {
		$encrypt = base64_decode($encrypt);
		$encrypt = substr($encrypt, 8);

		$key = config('app.app_key');
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);

		$iv = substr($encrypt, $iv_size);

		$encrypt = substr($encrypt, 0, $iv_size);

		$decrypt = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $encrypt, MCRYPT_MODE_CBC, $iv);

		return trim($decrypt);
	}
}

if (!function_exists('redirect')) {
	function redirect($url, $status = 302, $header = []) {
		return app('response')->redirect($url, $status, $header);
	}
}

if (!function_exists('back')) {
	function back($go = false) {
		if (!$go) {
			$back = session('referer.' . request()->path());
			return $back;
		}

		return redirect(back());
	}
}

if (!function_exists('app_path')) {
	function app_path($path) {
		return app()->path($path);
	}
}

if (!function_exists('public_path')) {
	function public_path($path) {
		return app()->publicPath($path);
	}
}

if (!function_exists('cache_path')) {
	function cache_path($path) {
		return app()->cachePath($path);
	}
}

if (!function_exists('start_with')) {
	function start_with($string, $start) {
		return substr($string, 0, strlen($start)) === $start;
	}
}

if (!function_exists('end_with')) {
	function end_with($string, $end) {
		if (is_array($end)) {
			foreach ($end as $val) {
				if (endWith($string, $val)) {
					return true;
				}
			}

			return false;
		}

		return substr($string, -1, strlen($end)) === $end;
	}
}

// if(!function_exists('csrf_field')) {
// 	function csrf_field() {
// 		$string =
// 		$token = encrypt()
// 		return '<input type="hidden" name="csrf_token" value="' .  . '">';
// 	}
// }

if (!function_exists('toUnderline')) {
	function toUnderline($string) {
		return strtolower(preg_replace("#((?<=[a-z])(?=[A-Z]))#", "_", $string));
	}
}
