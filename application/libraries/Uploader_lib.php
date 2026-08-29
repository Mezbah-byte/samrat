<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Image upload wrapper.
 *
 * Validates by extension AND by real MIME type, stores under a random name,
 * and never trusts the client-supplied filename. Combined with the
 * `uploads/.htaccess` handler block, an uploaded file cannot be executed.
 */
class Uploader_lib {

	const MAX_BYTES = 4194304; // 4 MB

	protected $CI;

	protected $allowed = array(
		'jpg'  => array('image/jpeg'),
		'jpeg' => array('image/jpeg'),
		'png'  => array('image/png'),
		'gif'  => array('image/gif'),
		'webp' => array('image/webp'),
	);

	/** @var string last failure reason */
	protected $error = '';

	public function __construct()
	{
		$this->CI =& get_instance();
	}

	public function error()
	{
		return $this->error;
	}

	/**
	 * @param string $field  name of the file input
	 * @param string $folder subdirectory under uploads/
	 * @return string|false stored filename, or FALSE (check error())
	 */
	public function image($field, $folder)
	{
		$this->error = '';

		if (empty($_FILES[$field]['name']))
		{
			$this->error = 'No file was selected.';
			return FALSE;
		}

		$file = $_FILES[$field];

		if ($file['error'] !== UPLOAD_ERR_OK)
		{
			$this->error = $this->php_upload_error($file['error']);
			return FALSE;
		}

		if ($file['size'] > self::MAX_BYTES)
		{
			$this->error = 'File is larger than '.round(self::MAX_BYTES / 1048576).' MB.';
			return FALSE;
		}

		if ( ! is_uploaded_file($file['tmp_name']))
		{
			$this->error = 'Invalid upload.';
			return FALSE;
		}

		$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

		if ( ! isset($this->allowed[$ext]))
		{
			$this->error = 'Only JPG, PNG, GIF and WEBP images are allowed.';
			return FALSE;
		}

		// The extension is a hint; the file's own bytes decide.
		$info = @getimagesize($file['tmp_name']);
		if ($info === FALSE || ! in_array($info['mime'], $this->allowed[$ext], TRUE))
		{
			$this->error = 'That file is not a valid image of the type its name claims.';
			return FALSE;
		}

		$dir = UPLOAD_PATH.$folder.DIRECTORY_SEPARATOR;
		if ( ! is_dir($dir) && ! @mkdir($dir, 0755, TRUE))
		{
			$this->error = 'Upload folder is not writable.';
			return FALSE;
		}

		$name = date('Ymd').'_'.bin2hex(random_bytes(8)).'.'.$ext;

		if ( ! @move_uploaded_file($file['tmp_name'], $dir.$name))
		{
			$this->error = 'Could not save the uploaded file.';
			return FALSE;
		}

		return $name;
	}

	/** Remove a previously stored file, guarding against path traversal. */
	public function remove($folder, $filename)
	{
		if (empty($filename))
		{
			return FALSE;
		}

		$filename = basename($filename);
		$path     = UPLOAD_PATH.$folder.DIRECTORY_SEPARATOR.$filename;

		return is_file($path) ? @unlink($path) : FALSE;
	}

	protected function php_upload_error($code)
	{
		switch ($code)
		{
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return 'The file is too large.';
			case UPLOAD_ERR_PARTIAL:
				return 'The file was only partially uploaded.';
			case UPLOAD_ERR_NO_FILE:
				return 'No file was selected.';
			case UPLOAD_ERR_NO_TMP_DIR:
			case UPLOAD_ERR_CANT_WRITE:
				return 'The server could not write the file to disk.';
			default:
				return 'Upload failed.';
		}
	}
}
