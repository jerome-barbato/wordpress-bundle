<?php

namespace App\Twig;

use Metabolism\WordpressBundle\Helper\ACF;

use Twig\Extension\AbstractExtension,
	Twig\TwigFilter,
	Twig\TwigFunction;

class AppExtension extends AbstractExtension{


	private $projectDir, $options, $locale;

	public function __construct( $defaultLocale, $projectDir, $emailSender )
	{
		$this->locale = strtolower(get_bloginfo('language'));
		$this->projectDir = $projectDir;
		//$this->getTranslations();
	}

	private function getTranslations()
	{
		$options = new ACF('options');
		$this->options = $options->get();

		if( isset($this->options['translations']) )
		{
			$translations = [];
			foreach ($this->options['translations'] as $translation)
			{
				$key = sanitize_title($translation['key']);
				$translations[$key] = $translation['translation'];
			}

			$this->options['translations'] = $translations;
		}
	}


	public function getFilters()
	{
		return [
			new TwigFilter( "protect_email", [$this,'protectEmail'], ['pre_escape' => 'html', 'is_safe' => ['html']] ),
			new TwigFilter( "youtube_id", [$this,'youtubeID'] ),
			new TwigFilter( "instagram_id", [$this,'instagramID'] ),
			new TwigFilter( "clean_id", [$this,'cleanID'] ),
			new TwigFilter( "ll_CC", [$this,'llCC'] ),
			new TwigFilter( "br_to_space", [$this,'brToSpace'] ),
			new TwigFilter( "remove_accent", [$this,'removeAccent'] ),
			new TwigFilter( "typeOf", [$this,'typeOf'] ),
			new TwigFilter( "bind", [$this,'bind'] ),
			new TwigFilter( "more", [$this,'more'] ),
			new TwigFilter( "implode", [$this,'implode'] ),
			new TwigFilter( 'striptag', [$this, 'striptag']),
			new TwigFilter( 'br_to_line', [$this, 'brToLine']),
			new TwigFilter( 'remove_br', [$this, 'removeBr']),
			new TwigFilter( 'file_content', [$this, 'getFileContent']),
			new TwigFilter( 'map_url', [$this, 'mapUrl']),
			new TwigFilter( 'wrap_embed', [$this, 'wrapEmbed']),
			new TwigFilter( 'format', [$this,'format'], ['is_safe' => array('html')] ),
			new TwigFilter( 'reading_time', [$this,'reading_time'] ),
			new TwigFilter( 'truncate', [$this, 'truncate']),
			new TwigFilter( 'clean', [$this, 'clean']),
			new TwigFilter( 'scrub', [$this, 'scrub'] )
		];
	}


	/**
	 * @return array
	 */
	public function getFunctions()
	{
		return [
			new TwigFunction( "__", [$this,'translate'] ),
			new TwigFunction( "GT", [$this,'GT'] ),
			new TwigFunction( "GTE", [$this,'GTE'] ),
			new TwigFunction( "LT", [$this,'LT'] ),
			new TwigFunction( "LTE", [$this,'LTE'] ),
			new TwigFunction( "blank", [$this,'blank'] ),
			new TwigFunction( 'store', [$this, 'store'])
		];
	}

	/**
	 * @param $url
	 * @param array $allowed_type
	 * @return void
	 */
	public function store($url, $allowed_type = ['image/jpeg', 'image/jpg', 'image/gif', 'image/png'])
	{
		$publicFolder = $this->projectDir.'/public/cache';
		$tmpFolder = $this->projectDir.'/var/tmp';
		$filename = substr(sha1($url), 0, 16);
		$tmpFile = $tmpFolder.'/'.$filename;
		$publicFile = $publicFolder.'/'.$filename;

		// check if file exists in cache, don't know the extension yet so glob it
		$files = glob($publicFile.'.*');

		if( count($files) )
			return str_replace($this->projectDir.'/public', '', $files[0]);

		//create folders
		if( !is_dir($publicFolder) )
			mkdir($publicFolder, 0755, true);

		if( !is_dir($tmpFolder) )
			mkdir($tmpFolder, 0755, true);

		//download file
		if( @file_put_contents($tmpFile, @file_get_contents($url)) ){

			//check type
			$mime_type = mime_content_type($tmpFile);

			if( !in_array($mime_type, $allowed_type) ){

				unlink($tmpFile);
				return $url;
			}

			$mime_type = explode('/', $mime_type);
			$file = $publicFile.'.'.$mime_type[1];

			//move to public folder with ext
			if( rename($tmpFile, $file) )
				return str_replace($this->projectDir.'/public', '', $file);
		}

		return $url;
	}


	public function translate($text)
	{
		$key = sanitize_title($text);

		if( isset($this->options['translations'], $this->options['translations'][$key]) )
			return $this->options['translations'][$key];
		else
			return __($text);
	}


	public function getFileContent($path)
	{
		if( file_exists($path) )
			return file_get_contents($path);

		return "file doesn't exists";
	}


	public function clean($string)
	{
		return str_replace('"',"'",   str_replace("'","\'",  $string));
	}


	public function scrub($string)
	{
		return trim(str_replace("\n","", str_replace("\r","",  str_replace("'","\'",  str_replace('"',"'", str_replace('<br />'," ",  $string))))));
	}


	/**
	 * @return string
	 */
	public function blank()
	{
		return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
	}


	public function wrapEmbed($content)
	{

		$content = preg_replace( "/<object/Si", '<div class="embed-container"><object', $content );
		$content = preg_replace( "/<\/object>/Si", '</object></div>', $content );

		$content = preg_replace( "/<iframe.+?src=\"(.+?)\"/Si", '<div class="embed-container"><iframe src="\1" frameborder="0" allowfullscreen>', $content );
		$content = preg_replace( "/<\/iframe>/Si", '</iframe></div>', $content );

		return $content;
	}


	public function striptag($string, $tag)
	{
		$tag = str_replace('<', '', str_replace('>', '', $tag ));
		return preg_replace("/<\\/?" . $tag . "(.|\\s)*?>/",'', $string);
	}


	public function brToLine($string)
	{
		return '<span>'.str_replace('<br/>', '</span><span>', str_replace('<br>', '</span><span>', str_replace('<br />', '</span><span>', $string))).'</span>';
	}


	public function removeBr($string)
	{
		return str_replace('<br/>', ' ', str_replace('<br>', ' ', str_replace('<br />', ' ', $string)));
	}


	public function implode($pieces, $glue = ",", $key = false)
	{
		if( !$key )
		{
			return implode($glue, $pieces);
		}
		else
		{
			$array = [];
			foreach ($pieces as $piece)
			{
				$piece = (array)$piece;

				if( isset($piece[$key]) )
					$array[] = $piece[$key];
			}

			return implode($glue, $array);
		}
	}


	/**
	 * Template translation of typeof function in PHP
	 *
	 * @see typeof
	 * @param      $var
	 * @param null $type_test
	 * @return bool
	 */
	public function typeOf($var, $type_test = null)
	{
		switch ( $type_test )
		{
			default:
				return false;
				break;

			case 'array':
				return is_array( $var );
				break;

			case 'bool':
				return is_bool( $var );
				break;

			case 'float':
				return is_float( $var );
				break;

			case 'int':
				return is_int( $var );
				break;

			case 'numeric':
				return is_numeric( $var );
				break;

			case 'object':
				if ( !is_array($var) ) return false;
				return array_keys($var) !== range(0, count($var) - 1);
				break;

			case 'scalar':
				return is_scalar( $var );
				break;

			case 'string':
				return is_string( $var );
				break;

			case 'datetime':
				return ( $var instanceof \DateTime );
				break;
		}
	}


	/**
	 * Email string verification.
	 *
	 * @param        $text
	 * @param string $mailto
	 * @return mixed
	 */
	public function protectEmail($text, $mailto = false)
	{
		preg_match_all( '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/', $text, $potentialEmails, PREG_SET_ORDER );

		$potentialEmailsCount = count( $potentialEmails );

		for ( $i = 0; $i < $potentialEmailsCount; $i++ )
		{
			if ( filter_var( $potentialEmails[$i][0], FILTER_VALIDATE_EMAIL ) )
			{
				$email = $potentialEmails[$i][0];
				$email = explode( '@', $email );

				$text = str_replace( $potentialEmails[$i][0], '<email name="' . $email[0] . '" domain="' . $email[1] . '" mailto="'.($mailto?1:0).'"></email>', $text );
			}
		}

		return $text;
	}


	/**
	 * Returns the video ID of a youtube video.
	 *
	 * @param $url
	 * @return string
	 */
	public function youtubeID($url)
	{
		preg_match( "/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $url, $matches );

		return count( $matches ) > 1 ? $matches[1] : '';
	}


	/**
	 * Returns the video ID of a youtube video.
	 *
	 * @param $url
	 * @return string
	 */
	public function instagramID($url)
	{
		preg_match('/https:\/\/www.instagram.com\/p\/(.+)\/(.*)/U', $url, $matches);
		return count( $matches ) > 1 ? $matches[1] : '';
	}

	/**
	 * Returns the reading time of the article based on the number of words.
	 *
	 * @param $layout
	 * @return string
	 */
	public function reading_time($layout)
	{
		$word = str_word_count(strip_tags(json_encode($layout)));
		$m = floor($word / 200);
		$est = $m . ' min';

		return $est;
	}


	/**
	 * format id
	 *
	 * @param $text
	 * @return string
	 */
	public function cleanID($text)
	{
		return ucfirst( str_replace( '/', ' - ', trim( trim( preg_replace( '/_|-/', ' ', $text ), '/' ) ) ) );
	}


	/**
	 * @param $locale
	 * @return string
	 */
	public function llCC($locale)
	{
		return $locale . '_' . strtoupper( $locale );
	}

	/**
	 * @param $text
	 * @return mixed
	 */
	public function brToSpace($text)
	{
		return preg_replace( '/\s+/', ' ', str_replace( '<br>', ' ', str_replace( '<br/>', ' ', str_replace( '<br />', ' ', $text ) ) ) );
	}

	/**
	 * @param $objects
	 * @param $attrs
	 * @return mixed
	 * @internal param $text
	 */
	public function bind($objects, $attrs)
	{
		$binded_objects = [];
		$objects = (array)$objects;

		foreach ($objects as $object)
		{
			$object = (array)$object;

			if( is_array($attrs) )
			{
				$binded_object = [];
				foreach ($attrs as $dest=>$source)
				{
					$binded_object[$dest] = isset($object[$source]) ? $object[$source] : false;
				}

				$binded_objects[] = $binded_object;
			}
			else
			{
				$binded_objects[] = isset($object[$attrs]) ? $object[$attrs] : false;
			}
		}

		return $binded_objects;
	}

	/**
	 * @param $text
	 * @return mixed
	 */
	public function cleanSpace($text)
	{
		return preg_replace( '/\s+/', ' ', str_replace( '<br>', ' <br/>', str_replace( '<br/>', ' <br/>', str_replace( '<br />', ' <br/>', $text ) ) ) );
	}

	/**
	 * @param        $text
	 * @param string $charset
	 * @return mixed|string
	 */
	public function removeAccent($text, $charset = 'utf-8')
	{
		$str = htmlentities( $text, ENT_NOQUOTES, $charset );

		$str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
		$str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str ); // pour les ligatures e.g. '&oelig;'
		$str = preg_replace( '#&[^;]+;#', '', $str ); // supprime les autres caractères

		return $str;
	}

	public function GT($reference, $compare)
	{
		return floatval($reference) > floatval($compare);
	}

	public function GTE($reference, $compare)
	{
		return floatval($reference) >= floatval($compare);
	}

	public function LT($reference, $compare)
	{
		return floatval($reference) < floatval($compare);
	}

	public function LTE($reference, $compare)
	{
		return floatval($reference) <= floatval($compare);
	}


	public function mapURL($map_field)
	{
		if( isset($map_field['lat'], $map_field['lng']))
			return "https://www.google.com/maps?daddr=".$map_field['lat'].",".$map_field['lng'];

		return false;
	}

	/**
	 * Format string from delimiter
	 */
	public function format($string, $tag=false) {

		if( !is_string($string) )
			return '';

		$strings = explode('<br />', trim($string));

		if( count($strings) > 1 || $tag ){

			if( $tag ){
				return '<'.$tag.'>'.implode('</'.$tag.'><'.$tag.'>', $strings).'</'.$tag.'>';
			}
			else{
				if( $this->locale == 'en-us')
					return '<span>'.trim($strings[0]).'</span><br/>'.trim($strings[1]);
				else
					return trim($strings[0]).'<br/><span>'.trim($strings[1]).'</span>';
			}
		}

		return $string;
	}

	public function truncate($string, $limit, $ellipsis=" ...")
	{
		$string = strip_tags($this->brToSpace($string));

		if (strlen($string) > $limit)
		{
			$string = wordwrap($string, intval($limit));
			return substr($string, 0, strpos($string, "\n")).$ellipsis;
		}

		return $string;
	}
}
