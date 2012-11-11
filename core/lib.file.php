<? 

	function readFileContentIntoArray($source_filename)
    {
    	$file = array();
        $file['source_filename'] = $source_filename;

        $file['timestamp'] = filemtime($source_filename);

        $segments = preg_split( '/\R\R/',  trim(cub_file_get_contents($source_filename)), 2);
        if (! isset($segments[1])) $segments[1] = '';
        if (count($segments) > 1) {
            // Read headers for Tag, Type values
            $headers = explode("\n", $segments[0]);
            $has_title_yet = false;
            
            foreach ($headers as $header) {
                if (isset($header[0]) && $header[0] == '=') {
                    $has_title_yet = true;
                    continue;
                }
                
                if (! $has_title_yet) {
                    $has_title_yet = true;
                    $file['title'] = $header;
                    continue;
                }
                
                
                $fields = explode(':', $header, 2);
                if (count($fields) < 2) continue;
                $fname = strtolower($fields[0]);
                $fields[1] = trim($fields[1]);
                if ($fname == 'tags') {
                     $file['tags'] = $fields[1];
                } else if ($fname == 'type') {
                     $file['type'] = str_replace('|', ' ', $fields[1]);
                } else if ($fname == 'published') {
                     $file['published'] = strtotime($fields[1]);
 
                } else {
                     $file['headers'][$fname] = $fields[1];
                }
                
                if (isset($file['headers']['link'])) $file['type'] = 'link';
            }
            array_shift($segments);
        }
        
        $file['body'] = isset($segments[0]) ? $segments[0] : '';
    
        return $file;
    }