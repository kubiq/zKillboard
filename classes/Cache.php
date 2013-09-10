<?php
/* zKillboard
 * Copyright (C) 2012-2013 EVE-KILL Team and EVSCO.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * zKillboard cache class
 */
class Cache
{
	function Cache()
	{
		trigger_error('The class "cache" may only be invoked statically.', E_USER_ERROR);
	}

	/**
	 * Initiate the cache
	 */
	protected static function getCache()
	{
		global $cache, $memcacheServer, $memcachePort;

		if ($cache == null)
		{
			if(extension_loaded("apcu"))
			{
				$cache = new ApcCache();
			}
			else if(extension_loaded("Memcached"))
			{
				$cache = new MemcachedCache();
			}
			else if(extension_loaded("memcache"))
			{
				$cache = new MemcacheCache();
			}
			else
			{
				$cache = new FileCache();
			}
		}

		return $cache;
	}

	/**
	 * Sets data to the cache
	 *
	 * @param $key
	 * @param $value
	 * @param $timeout
	 * @return bool
	 */
	public static function set($key, $value, $timeout = '3600')
	{
		$cache = Cache::getCache();
		return $cache->set($key, $value, $timeout);
	}

	/**
	 * Gets data from the cache
	 *
	 * @param $key
	 * @return array
	 */
	public static function get($key)
	{
		$cache = Cache::getCache();
		return $cache->get($key);
	}

	/**
	 * Deletes data from the cache
	 *
	 * @param $key
	 * @return bool
	 */
	public static function delete($key)
	{
		$cache = Cache::getCache();
		return $cache->delete($key);
	}

	/**
	 * Replace a value, if it exists
	 *
	 * @param $key
	 * @param $value
	 * @param $timeout
	 * @return bool
	 */
	public static function replace($key, $value, $timeout = '3600')
	{
		$cache = Cache::getCache();
		return $cache->replace($key, $value, $timeout);
	}

	/**
	 * Increment a value
	 *
	 * @param $key
	 * @param $timeout (This only works for Memcached, file cache flat out ignores it)
	 * @return new value on success, else false
	 */
	public static function increment($key, $timeout = 3600)
	{
		$cache = Cache::getCache();
		return $cache->increment($key, 1, $timeout);
	}

	/**
	 * Decrement a value
	 *
	 * @param $key
	 * @param $timeout (This only works for Memcached, file cache flat out ignores it)
	 * @return new value on success, else false
	 */
	public static function decrement($key, $timeout = 3600)
	{
		$cache = Cache::getCache();
		return $cache->decrement($key, 1, $timeout);
	}

	/**
	 * Flush the Cache
	 *
	 * @return bool
	 */
	public static function flush()
	{
		$cache = Cache::getCache();
		return $cache->flush();
	}
}
