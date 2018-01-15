<?php

/**
 *      [CodeZm!] Author CodeZm[codezm@163.com].
 *
 *      manage - ssh.
 *      $Id: manage.php 2018-01-14 18:04:38 codezm $
 */

class Manage {

    // Current path.
    private $path;
    // The data directory under current path.
	private $data;
    // The data file name.
    private $storageFileName = 'ssh';
    private $default_icon_png = 'icon.png';
    // The output result data.
    private $results = array();
    // The default separator.
    private $env_separator = ' ';
    private $show_separator = ' | ';
    // Subtitle tips.
    private $subtitle = array(
        'operate' => 'Tips: <Enter> to use | <Shift> to delete | <Cmd> to copy!'
    );
    // Read file data with cache.
    private $readFileData = array();

    /**
     * 管理
     *
     */
    public function __construct($query, $mode) {
        /* {{{ */

        $this->path = exec('pwd');
        $this->data = $this->path . '/data';

        // Mkdir.
		if (!file_exists($this->data)):
			exec("mkdir '" . $this->data . "'");
		endif;

        $this->query = $query;
        $this->env_separator = !empty(getenv('separator')) ? getenv('separator') : ' ';

        if (empty($mode)) {
            $mode = 'showQueryResult';
        }

        $this->$mode();

        /* }}} */
    }

    /**
     * 添加数据.
     *
     */
    public function insert() {
        /*{{{*/
        $fileData = $this->readFileData($this->storageFileName);
        list($query, $operate) = explode($this->show_separator, $this->query);
        if (!empty($operate)) {
            switch ($operate) {
                case 'do':
                case 'add':
                    if (empty($fileData)) {
                        $fileData = array();
                    }
                    if (empty($fileData) || !in_array($query, $fileData)) {
                        $fileData[] = $query; 
                        $this->writeFileData($this->storageFileName, $fileData);
                    }
                    break;
                default:
                    break;
            }
            //exit;
        }

        // Remove service name.
        $query = explode($this->env_separator, $query);
        array_shift($query);
        echo "/usr/bin/expect " . $this->path . "/login.expect " . implode($this->env_separator, $query);
        exit;
        /*}}}*/
    }

    /**
     * 删除数据.
     *
     */
    public function delete() {
        /*{{{*/

        $fileData = $this->readFileData($this->storageFileName);
        if (($key = array_search($this->query, $fileData)) !== false) {
            unset($fileData[$key]);
            $this->writeFileData($this->storageFileName, $fileData);
        }
        echo $this->query;
        exit;

        /*}}}*/
    }

    /**
     * 复制数据.
     *
     */
    public function copy() {
        /*{{{*/
        
        $query = explode($this->env_separator, $this->query);
        $query = implode("\n", $query);
        echo $query;
        exit;

        /*}}}*/
    }

    /**
     * 展示查询结果
     *
     */
    public function showQueryResult() {
        /*{{{*/ 
        $query = $this->query;
        $fileData = $this->readFileData($this->storageFileName);

        $data = array(
            'uid' => 'codezm', 
            'arg' => $query, 
            'title' => 'Auto login by ssh - Tips: You haven\'t added any thing.',
            'subtitle' => 'Please add data by this format: server-name ip-addr uname pwd [root-pwd] | do',
            'valid' => false,
            'autocomplete' => '', 
        );
        if (!empty($query) && stripos($query, $this->show_separator) !== false) {
            list($tmp, $operate) = explode($this->show_separator, $query);
            if (in_array($operate, array('add', 'do'))) {
                $data['title'] = 'Input <enter> to save.';
                $data['valid'] = true;
                unset($data['autocomplete']);
            }
            $this->addOutputResult($data);
            $this->output();
        }

        // Check empty data.
        if (empty($fileData)) {
            $this->addOutputResult($data);
            $this->output();
        }

        // Matching query data.
        foreach ($fileData as $key => $elementData) {
            $originalElementData = $elementData;
            $elementData = explode($this->env_separator, $elementData);
            //echo '<pre>'; var_dump($elementData); echo '</pre>'; die();

            if (empty($query)):
                // Show all storage data.
                $this->addOutputResult(array(
                    'uid' => $key,
                    'arg' => $originalElementData,
                    'title' => $elementData[0] . $this->show_separator . $elementData[1],
                    'subtitle' => $this->subtitle['operate'],
                    'valid' => true,
                    //'autocomplete' => false
                ));
            elseif ($query && (stripos($elementData[0], $query) !== false || stripos($elementData[1], $query) !== false)):
                $this->addOutputResult(array(
                    'uid' => $key,
                    'arg' => $originalElementData,
                    'title' => $elementData[0] . $this->show_separator . $elementData[1],
                    'subtitle' => $this->subtitle['operate'],
                    'valid' => true,
                    //'autocomplete' => false
                ));
            endif;
        }

        // Not matching query data.
        if (empty($this->results)) {
            $this->addOutputResult(array(
                'title' => 'Not Found!',
                'subtitle' => $this->subtitle['operate'],
                'valid' => false
            ));
        }
        $this->output();
        /*}}}*/
    }

    /**
     * 获取文件数据.
     *
     */
    private function readFileData($filename, $formatToArray = true) {
        /*{{{*/
		if (file_exists($filename)):
			$filename = $this->path . '/' . $filename;
		elseif (file_exists($this->data . '/' . $filename)):
			$filename = $this->data . '/' . $filename;
		else:
			return false;
		endif;

        // Get cache data.
        if (isset($this->readFileData[$filename])) {
            return $this->readFileData[$filename];
        }

		$out = file_get_contents($filename);
		if (!is_null(json_decode($out))):
			$out = json_decode($out, $formatToArray);
		endif;

        // Set cache data.
        $this->readFileData[$filename] = $out;

        // Return data.
		return $out;
        /*}}}*/
    }

	/**
	* Description:
	* Accepts data and a string file name to store data to local file as cache
	*
    * @param string $fileName - filename to write the cache data to
	* @param string or array $data - data to save to file
	* @return bool 
	*/
	private function writeFileData($fileName, $data) {
        /*{{{*/
		if (file_exists($fileName)):
			$fileName = $this->path . '/' . $fileName;
		else:
			$fileName = $this->data . '/' . $fileName;
		endif;

		if (is_array($data)):
			$data = json_encode($data);
			file_put_contents($fileName, $data);
			return true;
		elseif (is_string($data)):
			file_put_contents($fileName, $data);
			return true;
		else:
			return false;
		endif;
        /*}}}*/
	}

    /**
     * output.
     */
    private function output() {
        /*{{{*/
        echo json_encode(array(
            'items' => $this->results
        ));
        exit;
        /*}}}*/
    }

    /**
     * Add output data element.
     *
     */
    private function addOutputResult($dataElement = array()) {
        /*{{{*/
		$defaultDataElement = array(
			'uid' => 1,
			'arg' => '',
			'title' => '',
			'subtitle' => '',
            'icon' => array(
                'path' => $this->default_icon_png
            ),
			'valid' => false,
			//'autocomplete' => false,
            'type' => 'default'
		);

        array_push($this->results, array_merge($defaultDataElement, $dataElement));
        /*}}}*/
    }
}

if (!isset($query)) {
    $query = $argv[1];
}
if (!isset($mode)) {
    $mode = isset($argv[2]) ? $argv[2] : '';
}
$manage = new Manage($query, $mode);
