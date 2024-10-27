<?php
/*========================================================================*\
|| ###################################################################### ||
|| # vBulletin 6.0.6 - Licence Number LN05842122
|| # ------------------------------------------------------------------ # ||
|| # Copyright 2000-2024 MH Sub I, LLC dba vBulletin. All Rights Reserved.  # ||
|| # This file may not be redistributed in whole or significant part.   # ||
|| # ----------------- VBULLETIN IS NOT FREE SOFTWARE ----------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html   # ||
|| ###################################################################### ||
\*========================================================================*/

class vB5_Frontend_Controller_Uploader extends vB5_Frontend_Controller
{
	private $api;

	public function __construct()
	{
		parent::__construct();
		$this->api = Api_InterfaceAbstract::instance();
	}

	public function actionGetUploader()
	{
		$config = vB5_Config::instance();

		$templater = new vB5_Template('attach_uploader');
		$this->outputPage($templater->render());
	}

	/**
	 * Fetches an image from a URL and adds it as an attachment.
	 *
	 * Used by: (not necessarily an exhaustive list)
	 * 	Content entry UI attachments panel, when uploading from a URL
	 * 	Content entry UI "image" button in toolbar, when fetching and saving as a local attachment
	 * 	Uploading a profile image / avatar, when uploading from a URL
	 * 	Uploading a signature pic, when uploading from a URL
	 * 	Uploading a group image, when uploading from a URL
	 * 	Uploading a site logo in sitebuilder, when uploading from a URL
	 */
	public function actionUrl()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if (isset($_REQUEST['urlupload']))
		{
			$api = Api_InterfaceAbstract::instance();

			$response = $api->callApi('content_attach', 'uploadUrl',
				[
					'url' => $_REQUEST['urlupload'],
					'attachment' => (!empty($_REQUEST['attachment']) ? $_REQUEST['attachment'] : ''),
					'uploadfrom' => $_REQUEST['uploadFrom'] ?? '',
				]
			);

			// when the api returns an error, there is no filedataid
			$response['filedataid'] = empty($response['filedataid']) ? 0 : $response['filedataid'];
			$response['filename'] = empty($response['filename'])? '' : $response['filename'];

			$response['imageUrl'] = 'filedata/fetch?filedataid=' . $response['filedataid'];
			$response['thumbUrl'] = 'filedata/fetch?filedataid=' . $response['filedataid'] . '&type=thumb';
			$response['deleteUrl'] = 'filedata/delete?filedataid=' . $response['filedataid'];

			$this->sendAsJson($response);
		}
	}

	/**
	 * Uploads an image and sets it as the logo in one step
	 */
	public function actionUploadLogoUrl()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if (isset($_POST['urlupload']))
		{
			$api = Api_InterfaceAbstract::instance();
			$response = $api->callApi('content_attach', 'uploadUrl', ['url' => $_POST['urlupload']]);

			if (!empty($response['errors']))
			{
				$this->sendAsJson($response);
				return;
			}

			$response2 = $api->callApi('content_attach', 'setLogo', ['filedataid' => $response['filedataid']]);
			if (!empty($response2['errors']))
			{
				$this->sendAsJson($response2);
				return;
			}

			$result['imageUrl'] = 'filedata/fetch?filedataid=' . $response['filedataid'];
			$result['thumbUrl'] = 'filedata/fetch?filedataid=' . $response['filedataid'] . '&thumb=1';
			$result['filedataid'] = $response['filedataid'];

			$this->sendAsJson($result);
		}
	}

	/**
	 * Uploads a file.
	 */
	public function actionUploadFile()
	{
		// require a POST request for this action
		$this->verifyPostRequest();
		if ($_FILES AND !empty($_FILES['file']))
		{
			$api = Api_InterfaceAbstract::instance();

			if (!empty($_REQUEST['uploadFrom']))
			{
				$_FILES['file']['uploadFrom'] = $_REQUEST['uploadFrom'];
			}

			if (!empty($_REQUEST['nodeid']))
			{
				$_FILES['file']['parentid'] = $_REQUEST['nodeid'];
			}

			$response = $api->callApi('content_attach', 'upload', ['file' => $_FILES['file']]);
			if (!empty($response['errors']))
			{
				return $this->sendAsJson($response);
			}

			$response['imageUrl'] = 'filedata/fetch?filedataid=' . $response['filedataid'];
			$response['thumbUrl'] = 'filedata/fetch?filedataid=' . $response['filedataid'] . '&type=thumb';
			$response['mediumUrl'] = 'filedata/fetch?filedataid=' . $response['filedataid'] . '&type=medium';
			$response['deleteUrl'] = 'filedata/delete?filedataid=' . $response['filedataid'];

			$this->sendAsJson($response);
		}
	}

	/**
	 * Uploads a photo. Returns an edit block and the photo URL.
	 */
	public function actionUploadPhoto()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if ($_FILES AND !empty($_FILES) )
		{
			if (!empty($_FILES['file']))
			{
				$fileData = $_FILES['file'];
			}
			else if (!empty($_FILES['files']))
			{
				if (is_array($_FILES['files']['name']))
				{
					$fileData = [
						'name' => $_FILES['files']['name'][0],
						'type' => $_FILES['files']['type'][0],
						'tmp_name' => $_FILES['files']['tmp_name'][0],
						'size' => $_FILES['files']['size'][0],
						'error' => $_FILES['files']['error'][0]
					];
				}
				else
				{
					$fileData = $_FILES['files'];
				}
			}

			if (isset($_POST['galleryid']))
			{
				$galleryid = intval($_POST['galleryid']);
			}
			else
			{
				$galleryid = '';
			}

			if (isset($_POST['uploadFrom']))
			{
				$fileData['uploadFrom'] = $_POST['uploadFrom'];
			}
			else
			{
				$fileData['uploadFrom'] = '';
			}

			$api = Api_InterfaceAbstract::instance();
			$response = $api->callApi('content_attach', 'uploadPhoto', ['file' => $fileData]);
			if (!empty($response['filedataid']))
			{
				$templater = new vB5_Template('photo_edit');
				$imgUrl = 'filedata/fetch?filedataid=' . $response['filedataid'] . "&type=thumb";
				$templater->register('imgUrl', $imgUrl);
				$templater->register('filedataid', $response['filedataid']);
				$response['edit'] = $templater->render();
				$response['imgUrl'] = $imgUrl;
				$response['galleryid'] = $galleryid;
			}
			//need this to avoid errors with iframe transport.
			header("Content-type: text/plain");
			$this->sendAsJson($response);
		}
	}

	/** This method uploads an image and sets it as the logo in one step **/
	public function actionUploadLogo()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if ($_FILES AND !empty($_FILES['file']))
		{
			$api = Api_InterfaceAbstract::instance();
			$response = $api->callApi('content_attach', 'upload', ['file' => $_FILES['file']]);

			if (!empty($response['errors']))
			{
				$this->sendAsJson($response);
				return;
			}

			if (empty($response['filedataid']))
			{
				echo 'unknown error';
				return;
			}

			$response2 = $api->callApi('content_attach', 'setLogo', ['filedataid' => $response['filedataid']]);
			if (!empty($response2['errors']))
			{
				$this->sendAsJson($response2);
				return;
			}

			$response['imageUrl'] = 'filedata/fetch?filedataid=' . $response['filedataid'];
			$response['thumbUrl'] = 'filedata/fetch?filedataid=' . $response['filedataid'] . '&type=thumb';
			$response['filedataid'] = $response['filedataid'];
			$this->sendAsJson($response);
		}
		else
		{
			echo "No files to upload";
		}
	}

	// This method sets an uploaded image as the logo
	public function actionSetlogo()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if (isset($_POST['filedataid']))
		{
			$api = Api_InterfaceAbstract::instance();
			$response = $api->callApi('content_attach', 'setLogo', [
				'filedataid' => $_POST['filedataid'],
				'styleselection' => trim($_POST['styleselection'] ?? 'current'),
			]);
			$this->sendAsJson($response);
		}
	}

	// This method sets an uploaded image as the favicon
	public function actionSetFavicon()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if (isset($_POST['filedataid']))
		{
			$api = Api_InterfaceAbstract::instance();
			$response = $api->callApi('content_attach', 'setFavicon', [
				'filedataid' => $_POST['filedataid'],
				'styleselection' => trim($_POST['styleselection'] ?? 'current'),
			]);
			$this->sendAsJson($response);
		}
	}

	// Used by ckeditor's Image dialog > Upload tab > Send it to server button. Look for filebrowserImageUploadUrl in ckeditor.js
	public function actionCKEditorInsertImage()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		$options = [
			'param_name' => 'upload',
			'uploadFrom' => 'CKEditorInsertImage'
		];

		$upload_handler = new blueImpUploadHandler($options, $this->api);

		header('Pragma: no-cache');
		header('Cache-Control: private, no-cache');
		header('Content-Disposition: inline; filename="files.json"');

		$upload_handler->post();
	}

	// This method uploads an image as filedata and returns an array of useful information including the filedataid and links to the image and the thumbnail
	public function actionUpload()
	{

		header('Pragma: no-cache');
		header('Cache-Control: private, no-cache');
		header('Content-Disposition: inline; filename="files.json"');

		$upload_handler = new blueImpUploadHandler(null, $this->api);

		switch ($_SERVER['REQUEST_METHOD'])
		{
			case 'HEAD':
			case 'GET':
				$upload_handler->get();
				break;
			case 'POST':
				$upload_handler->post();
				break;
			case 'DELETE':
				$upload_handler->delete();
				break;
			default:
				http_response_code(405);
		}
	}

	/**
	 * This sets a social group/blog picture. Not currently used when changing icon from the *blog* admin settings page.
	 */
	public function actionUploadSGIcon()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		//Let's just let the API handle this.
		$api = Api_InterfaceAbstract::instance();
		if (!empty($_FILES['file']))
		{
			if (!empty($_REQUEST['uploadFrom']))
			{
				$_FILES['file']['uploadFrom'] = $_REQUEST['uploadFrom'];
			}
			else
			{
				$_FILES['file']['uploadFrom'] = 'sgicon';
			}

			if (!empty($_REQUEST['nodeid']))
			{
				$_FILES['file']['parentid'] = $_REQUEST['nodeid'];
			}

			$response = $api->callApi('content_attach', 'upload', ['file' => $_FILES['file']]);
		}
		else if (!empty($_REQUEST['url']))
		{
			$response = $api->callApi('content_attach', 'uploadUrl', ['url' => $_REQUEST['url']]);
		}
		else
		{
			throw new Exception('error_attachment_missing');
		}

		if (!empty($response['errors']))
		{
			return $this->sendAsJson($response);
		}

		$filedataid = $response['filedataid'];

		// default = update the channel (which is the previous behavior, also used by blogs)
		if (!isset($_REQUEST['updatechannel']) OR !empty($_REQUEST['updatechannel']))
		{
			$response = $api->callApi('content_channel', 'update', [$_REQUEST['nodeid'], ['filedataid' => $response['filedataid']]]);

			if (!empty($response['errors']))
			{
				return $this->sendAsJson($response);
			}
		}

		$response = [];
		$response['filedataid'] = $filedataid;
		$response['imageUrl'] = 'filedata/fetch?filedataid=' . $filedataid;
		$response['thumbUrl'] = 'filedata/fetch?filedataid=' . $filedataid . '&type=thumb';
		$response['mediumUrl'] = 'filedata/fetch?filedataid=' . $filedataid . '&type=medium';
		$response['deleteUrl'] = 'filedata/delete?filedataid=' . $filedataid;

		$this->sendAsJson($response);
	}

	/*
	 *	Replaces an existing attachment's settings with the new setting as provided
	 *	in the $_REQUEST data. Note, existing means it has a nodeid.
	 *
	 *	Used by ckeditor.js's vBulletin.ckeditor.modifyDialogs() and content_entry_box.js's vBulletin.contentEntryBox.handleAttachmentControl()
	 */
	public function actionSaveAttachmentSetting()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if (empty($_REQUEST['attachmentid']) OR !intval($_REQUEST['attachmentid']))
		{
			$response = ['error' => 'Invalid Attachmentid'];	// todo create phrase for this?
			$this->sendAsJson($response);
		}

		$data = [];
		$data['nodeid'] = intval($_REQUEST['attachmentid']);

		// filedataid, filename required per content_attach API's cleanInput(). Let's grab the
		// existing data.
		$fileInfo = $this->api->callApi('content_attach', 'fetchImage', ['id' => $data['nodeid']]);

		if (!empty($fileInfo['errors']))
		{
			$this->sendAsJson($fileInfo);
			return;
		}
		else if (empty($fileInfo) OR empty($fileInfo['filedataid']) OR empty($fileInfo['filename']))
		{
			$response = ['error' =>  'Failed to fetch the necessary attachment information'];
			$this->sendAsJson($response);
			return;
		}

		$data['filedataid'] = $fileInfo['filedataid'];
		$data['filename'] = $fileInfo['filename'];

		// We only use $availableSettings so we know which values to extract
		// from the $_POST variable. This is not here for cleaning,
		// which happens in the API. See the text and attach API cleanInput
		// methods.
		$settings = [];
		$availableSettings =  $this->api->callApi('content_attach', 'getAvailableSettings', []);
		$availableSettings = (isset($availableSettings['settings'])? $availableSettings['settings'] : []);
		foreach ($availableSettings AS $key)
		{
			if (isset($_REQUEST[$key]))
			{
				$settings[$key] = $_REQUEST[$key];
			}
		}
		$data['settings'] = $settings;

		// try to update
		$attachid =  $this->api->callApi('content_attach', 'update',
			[
				'nodeid' => $data['nodeid'],
				'data' => $data
			]
		);

		if (!empty($attachid['errors']))
		{
			$this->sendAsJson($attachid);
			return;
		}

		$response = ['success' => true];
		$this->sendAsJson($response);
	}

	/*
	 *	Fetches the filedataid given idname & idvalue, typically set in the queryparams of
	 *	a filedata fetch URL (ex. /filedata/fetch?id=1234). Currently only handles the case
	 *	idname = 'id', which points to an attachment's nodeid.
	 *
	 *	Used by ckeditor.js's vBulletin.ckeditor.modifyDialogs()
	 */
	public function actionFetchFiledataid()
	{
		// require a POST request for this action
		$this->verifyPostRequest();

		if (empty($_REQUEST['idname']) OR empty($_REQUEST['id']) OR !intval($_REQUEST['id']))
		{
			$response = ['error' => 'Invalid Parameters'];
			$this->sendAsJson($response);
			return;
		}

		$idname = $_REQUEST['idname'];
		$id = intval($_REQUEST['id']);

		$fileInfo = false;
		switch ($idname)
		{
			case 'id':
				$fileInfo = $this->api->callApi('content_attach', 'fetchImage', ['id' => $id]);
				break;
			default:
				break;
		}

		if (!empty($fileInfo['errors']))
		{
			$this->sendAsJson($fileInfo);
			return;
		}

		if (isset($fileInfo['filedataid']))
		{
			$response = ['filedataid' => $fileInfo['filedataid']];
			$this->sendAsJson($response);
			return;
		}
		else
		{
			$response = ['error' => 'Failed to fetch filedataid'];
			$this->sendAsJson($response);
			return;
		}
	}
}

class blueImpUploadHandler
{
	private $options;
	private $partials = [];
	private $fileData = [];
	private $api;
	private $baseurl;

	public function __construct($options, $api)
	{
		$this->api = $api;
		$this->baseurl = vB5_Template_Options::instance()->get('options.frontendurl');
		$this->options = [
			'script_url' => $_SERVER['PHP_SELF'],
			'param_name' => 'files',
			// The php.ini settings upload_max_filesize and post_max_size
			// take precedence over the following max_file_size setting:
			'max_file_size' => 500000,
			'min_file_size' => 1,
			'accept_file_types' => '/.+$/i',
			'max_number_of_files' => 5,
			'discard_aborted_uploads' => true,
			'image_versions' => []
		];

		if ($options)
		{
			$this->options = array_merge($this->options, $options);
		}
	}

	//these functions appear to be part of the "get" upload action which I don't think
	//are used.  They are not consistent with the return from the post and I'm not
	//sure the fileData array actually gets populated before this function is called.
	private function get_file_object($file_name)
	{
		if (array_key_exists($file_name, $this->fileData))
		{
			return $this->fileInfoToFileObj($file_name, $this->fileData[$file_name]);
		}
		return null;
	}

	private function get_file_objects()
	{
		$files = [];
		foreach ($this->fileData AS $filename => $fileInfo)
		{
			//this is almost certainly incorrect behavior.  The function was refactored in commit #45460
			//and it looks like a copy/paste error caused the return to be a single file object rather
			//than the array it previously returned.  Given this hasn't been discovered since, this function
			//is probably not used in practice.  Declining to fix at this time due to regression risk.
			$file = $this->fileInfoToFileObj($filename, $fileInfo);
			return $file;
		}
	}

	private function fileInfoToFileObj($filename, $fileInfo)
	{
		$file = new stdClass();
		$file->name = $filename;
		$file->size = $fileInfo['filesize'];
		$file->filedataid = $fileInfo['filedataid'];
		$file->url = $fileInfo['url'];
		$file->delete_url = $fileInfo['delete_url'];
		$file->thumb_url = $fileInfo['thumb_url'];
		$file->extension = $fileInfo['extension'];
		$file->basetype = $fileInfo['basetype'];
		$file->delete_type = 'DELETE';
		return $file;
	}

	private function has_error($uploaded_file, $file, $error)
	{
		if ($error)
		{
			return $error;
		}

		if ($uploaded_file && is_uploaded_file($uploaded_file))
		{
			$file_size = filesize($uploaded_file);
		}
		else
		{
			$file_size = $_SERVER['CONTENT_LENGTH'];
		}

		if ($this->options['max_file_size'] && (
			$file_size > $this->options['max_file_size'] ||
			$file->size > $this->options['max_file_size'])
		)
		{
			return 'maxFileSize';
		}
		if (
			$this->options['min_file_size'] &&
			$file_size < $this->options['min_file_size']
		)
		{
			return 'minFileSize';
		}

		if (
			is_int($this->options['max_number_of_files']) &&
			(count($this->fileData) >= $this->options['max_number_of_files'])
		)
		{
			return 'maxNumberOfFiles';
		}
		return $error;
	}

	private function handle_file_upload($uploaded_file, $name, $size, $type, $error)
	{
		$file = new stdClass();
		$file->name = basename(stripslashes($name));
		$file->size = intval($size);
		$file->type = $type;

		if (!empty($_POST['uploadFrom']))
		{
			$file->uploadFrom = $_POST['uploadFrom'];
		}
		if (!empty($_POST['parentid']))
		{
			$file->parentid = $_POST['parentid'];
		}
		// need to pass in any upload errors to API
		if (!empty($error) AND is_numeric($error))
		{
			$file->error = intval($error);
		}

		// Validation is and should be done in the API
		//$error = $this->has_error($uploaded_file, $file, $error);

		if ($file->name)
		{
			if ($file->name[0] === '.')
			{
				$file->name = substr($file->name, 1);
			}

			$append_file = $file->size > filesize($uploaded_file);

			if ($uploaded_file && is_uploaded_file($uploaded_file))
			{
				// multipart/formdata uploads (POST method uploads)
				if ($append_file)
				{
					if (!array_key_exists($file->name, $this->partials))
					{
						$this->partials[$file->name] = '';
					}
					$this->partials[$file->name] .= file_get_contents($uploaded_file);
					$file_size = strlen($this->partials[$file->name]);

					// if we don't hit this case then $fileInfo won't be defined below.
					if ($file_size >= $file->size)
					{
						$file->contents = $this->partials[$file->name] ;
						$file->size = $file_size;
						$fileInfo = $this->api->callApi('content_attach', 'upload', [$file]);
					}
				}
				else
				{
					$file_size = filesize($uploaded_file);
					// todo: set tmp_name so we can check is_uploaded_file() in API???
					$file->contents = file_get_contents($uploaded_file);
					$fileInfo = $this->api->callApi('content_attach', 'upload', [$file]);
				}
			}
			else
			{
				// Non-multipart uploads (PUT method support)
				// It's not clear that this can even be called -- upstream we only handle GET/POST/DELETE methods
				$file_size = filesize($uploaded_file);
				$file->tmp_name = $uploaded_file;
				$fileInfo = $this->api->callApi('content_attach', 'upload', [$file]);
			}

			// this is quite large and we don't need it once the attachment is created.
			unset($file->contents);

			if (!empty($fileInfo['errors']))
			{
				// return all errors. Image upload might have multiple error messages regarding resize failures.
				$file->error = $fileInfo['errors'];
			}
			else
			{
				// There appears to be a use case where the file is posted in the form at an array of multiple partial files.
				// In this case we only create the attachment (and thus get a fileInfo object) when the size of the collected
				// fragments reaches the total size of the file (which we expect to be the full size on every fragement).
				// This is just from reading the code, I'm not clear on what standards this is following and have no documentation
				// for the behavior.
				//
				// This goes all the way back to the original implementation and it's not clear that it's even been used.
				// Morever if this situation does happen we'll create a "file" object for each fragment (even if it doesn't have
				// the extra attachment information).  Worse we'll send that list back to the browser.  At least one case assumes
				// that the first element of the file array is the valid file with full information -- which will not be case
				// if there are multiple fragments.  This all suggests that this case is actually not used.
				if (isset($fileInfo))
				{
					$file->url = 'filedata/fetch?filedataid=' . $fileInfo['filedataid'] ;
					$file->thumbnail_url = 'filedata/fetch?filedataid=' . $fileInfo['filedataid'] . '&type=thumb' ;

					$this->fileData[$name] = $fileInfo;
					$this->fileData[$name]['url'] = 'filedata/fetch?filedataid=' . $fileInfo['filedataid'] ;
					$this->fileData[$name]['thumbnail_url'] = 'filedata/fetch?filedataid=' . $fileInfo['filedataid'] . '&type=thumb' ;
					$this->fileData[$name]['delete_url'] ='filedata/delete?filedataid=' . $fileInfo['filedataid'] ;

					$file->filedataid = $fileInfo['filedataid'] ;

					$file->name = $fileInfo['filename'];
					$file->extension = $fileInfo['extension'];
					$file->basetype = $fileInfo['basetype'];
				}

				$file->size = $file_size;
				$file->delete_url =  $this->baseurl . '/filedata/delete?filedataid=' . $fileInfo['filedataid'] ;
				$file->delete_type = 'DELETE';
			}
		}
		else
		{
			$file->error = $error;
		}

		return $file;
	}

	//I don't think this is used or can even *do* anything useful.
	public function get()
	{
		if (empty($_FILES) AND empty($_REQUEST['file']))
		{
			$controller = new vB5_Frontend_Controller();
			$controller->sendAsJson([]);
			return ;
		}

		$file_name = isset($_REQUEST['file']) ? basename(stripslashes($_REQUEST['file'])) : null;
		if ($file_name)
		{
			$info = $this->get_file_object($file_name);
		}
		else
		{
			$info = $this->get_file_objects();
		}

		$controller = new vB5_Frontend_Controller();
		$controller->sendAsJson($info);
	}

	public function post()
	{
		if (isset($_FILES[$this->options['param_name']]))
		{
			$upload = $_FILES[$this->options['param_name']];
		}
		else
		{
			$upload =  [
				'tmp_name' => null,
				'name' => null,
				'size' => null,
				'type' => null,
				'error' => 'no_file_to_upload',
			];
		}

		$info = [];
		if (is_array($upload['tmp_name']))
		{
			foreach ($upload['tmp_name'] AS $index => $value)
			{
				//this seems extremely dubious to use the $_SERVER variables over all of elements in the array
				$info[] = $this->handle_file_upload(
					$upload['tmp_name'][$index],
					$_SERVER['HTTP_X_FILE_NAME'] ?? $upload['name'][$index],
					$_SERVER['HTTP_X_FILE_SIZE'] ?? $upload['size'][$index],
					$_SERVER['HTTP_X_FILE_TYPE'] ?? $upload['type'][$index],
					$upload['error'][$index]
				);
			}
		}
		else
		{
			$info[] = $this->handle_file_upload(
				$upload['tmp_name'],
				$_SERVER['HTTP_X_FILE_NAME'] ?? $upload['name'],
				$_SERVER['HTTP_X_FILE_SIZE'] ?? $upload['size'],
				$_SERVER['HTTP_X_FILE_TYPE'] ?? $upload['type'],
				$upload['error']
			);
		}

		header('Vary: Accept');

		if (isset($this->options['uploadFrom']) AND $this->options['uploadFrom'] == 'CKEditorInsertImage')
		{
			header('Content-type: text/html');

			$funcNum = $_GET['CKEditorFuncNum'];
			$editorId = $_GET['CKEditor'];

			$api = Api_InterfaceAbstract::instance();

			if (!empty($info))
			{
				$url = $info[0]->url ?? '';
				$error = $info[0]->error ?? '';
			}
			else
			{
				$url = '';
				$error = 'error_uploading_image';
			}

			if (is_array($error))
			{
				if (!is_array($error[0]))
				{
					$error = [$error];
				}

				$phrases = $api->callApi('phrase', 'renderPhrases', [$error]);
				$error = implode("\n", $phrases['phrases']);
			}
			else if (!empty($error))
			{
				$phrases = $api->callApi('phrase', 'renderPhrases', [[$error]]);
				$error = $phrases['phrases'][0];
			}

			//encode to ensure we don't encounter js syntax error
			$errorEncode = json_encode($error);

			echo "<script type=\"text/javascript\">window.parent.CKEDITOR.tools.callFunction($funcNum, '$url', $errorEncode);";

			$havefile = false;
			foreach ($info AS $file)
			{
				if (!empty($file->filedataid))
				{
					// vBulletin.ckeditor.insertImageAttachment() is now called downstream of vBulletin.ckeditor.closeFileDialog()
					//echo "window.parent.vBulletin.ckeditor.insertImageAttachment('$editorId', {$file->filedataid}, '{$file->name}');";
					$havefile = true;
					break;
				}
			}

			if ($havefile AND empty($error))
			{
				// the image inserts (<img> element in the editor body & hidden inputs in the form) are handled as
				// part of closeFileDialog() (normally would be handled by the onOk handler of the dialog)
				echo "window.parent.vBulletin.ckeditor.closeFileDialog('$editorId', " . json_encode($info) . ");";
			}

			echo "</script>";
			exit;
		}

		if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false))
		{
			header('Content-type: application/json');
		}
		else
		{
			header('Content-type: text/plain');
		}

		$controller = new vB5_Frontend_Controller();
		$controller->sendAsJson($info);
	}

	public function delete()
	{
	}
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 06:53, Sun Oct 27th 2024
|| # CVS: $RCSfile$ - $Revision: 116229 $
|| #######################################################################
\*=========================================================================*/
