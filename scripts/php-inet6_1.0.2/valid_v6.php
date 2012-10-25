<?php
require ("inet6.php");    
function valid_ipv6_address( $ipv6 )
{
    $regex = '/^(((?=(?>.*?(::))(?!.+\3)))\3?|([\dA-F]{1,4}(\3|:(?!$)|$)|\2))(?4){5}((?4){2}|(25[0-5]|(2[0-4]|1\d|[1-9])?\d)(\.(?7)){3})\z/i';
        if(!preg_match($regex, $ipv6))
        return (false); // is not a valid IPv6 Address

    return true;
}

/**
    * Validate an IPv6 IP address
	 *
	 * @param  string $ip
	 * @return boolean - true/false
 */
function isValidIPv6($ip)
{
   if ( false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) )
  {
       return false;
	   }
       return inet6_compress($ip);
	}
    
    