<?php
#region ProtocolStreamWrapperProvider
interface ProtocolStreamWrapperProvider {
/**
* Get the protocol name that refers to this stream wrapper.
*
* For use with file_get_contents, e.g. file_get_contents(HTTP::GetStreamWrapperName() . '://hostname/...');
* @return string
*/
public static function GetProtocolName();

/**
* Registers the stream wrapper for this Protocol.
*
* Makes available this stream wrapper.
* @return void
*/
public static function RegisterStreamWrapper();

/**
* Get a resource/stream for this Protocol which can be used like any other file handle.
*
* Similar to calling fopen with GetProtocolName() as the protocol name.
* @param string $uri The URI or Path to open using this protocol.
* @param string $mode The read/write mode to use for opening the stream.
* @return resource
* @throws \Quark\Exception
* @see fopen()
*/
public static function GetStream($uri, $mode);
}

trait baseProtocolStreamWrapperProvider {
/**
* @var string The string used as protocol when using php streams that this stream wrapper will implement.
*/
protected static $wrapperName = '';

/**
* @var bool Whether or not this protocol uses urls to define it's path rather than local files paths for example.
*/
protected static $isURLProtocol = false;

/**
* Get the protocol name.
* @return bool
*/
public static function GetProtocolName(){
if(!empty(self::$wrapperName))
return self::$wrapperName;
else
return false;
}

/**
* Registers the stream wrapper for this Protocol.
*
* Makes available this stream wrapper.
* @return void
*/
public static function RegisterStreamWrapper(){
stream_wrapper_register(self::GetProtocolName(), get_called_class(), self::$isURLProtocol ? STREAM_IS_URL : 0);
}

/**
* Get a resource/stream for this Protocol which can be used like any other file handle.
*
* Similar to calling fopen with GetProtocolName() as the protocol name.
* @param string $uri The URI or Path to open using this protocol.
* @param string $mode The read/write mode to use for opening the stream.
* @return resource
* @throws \Quark\Exception
* @see fopen()
*/
public static function GetStream($uri, $mode){
// Make sure the correct protocol is used
$pos = strpos($uri, '://');
if($pos > -1)
$uri = self::GetProtocolName().substr($uri, $pos);
else
$uri = self::GetProtocolName().'://'.$uri;

// Create handle
$handle = @fopen($uri, $mode);
if($handle === false)
throw new Exception('ProtocolStreamWrapperProvider: Unable to create stream for the uri "'.$uri.'".');

return $handle;
}
}
#endregion

#region ProtocolStreamWrapper
/**
* Protocol Stream Wrapper Interface
*
* When implemented defines that that Protocol class is registrable as a php Stream Wrapper.
* @package Quark\Services\Protocols
*/
interface ProtocolStreamWrapper {
public function stream_open();

public function stream_read();

public function stream_write();

public function stream_tell();

public function stream_eof();

public function stream_seek();

public function stream_metadata();

public function stream_close();

public function stream_lock();

}
#endregion