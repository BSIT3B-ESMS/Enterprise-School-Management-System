<?php
namespace App\Services;
class Totp {
 private const ALPHABET='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
 public static function secret():string{$bits='';foreach(str_split(random_bytes(20)) as $c)$bits.=str_pad(decbin(ord($c)),8,'0',STR_PAD_LEFT);$out='';foreach(str_split($bits,5) as $b)$out.=self::ALPHABET[bindec(str_pad($b,5,'0'))];return $out;}
 private static function decode(string $secret):string{$bits='';foreach(str_split($secret) as $c){$i=strpos(self::ALPHABET,$c);if($i===false)throw new \InvalidArgumentException('Invalid authenticator secret');$bits.=str_pad(decbin($i),5,'0',STR_PAD_LEFT);}$out='';foreach(str_split($bits,8) as $b)if(strlen($b)===8)$out.=chr(bindec($b));return $out;}
 public static function code(string $secret,int $counter):string{
  $hash=hash_hmac('sha1',pack('N2',intdiv($counter,4294967296),$counter%4294967296),self::decode($secret),true);$offset=ord($hash[19])&15;$n=unpack('N',substr($hash,$offset,4))[1]&0x7fffffff;return str_pad((string)($n%1000000),6,'0',STR_PAD_LEFT);
 }
 public static function counter(string $secret,string $code,?int $last=null):?int{
  if(!preg_match('/^\d{6}$/',$code))return null;$current=intdiv(now()->timestamp,30);
  foreach([-1,0,1] as $d){$c=$current+$d;if(($last===null||$c>$last)&&hash_equals(self::code($secret,$c),$code))return $c;}return null;
 }
}
