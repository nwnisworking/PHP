<?php
/**
 * Element class
 * 
 * Easy way of creating HTML tag using PHP. 
 * 
 * Example usage:
 * 
 * Element::tag('div', 'hello world', ['name'=>'content']);
 * 
 * &lt;div name='content'&gt;hello world&lt;/div&gt;
 */
class Element{
	/** Element tag name*/
	public string $tag;
	/** Element text*/
	public ?string $text;
	/** Element attribute */
	public array|string $attr = [];

	public function __construct(string $tag, string|null $text = '', array|string $attr = []){
		$this->tag = $tag;
		$this->text = $text;
		$this->attr = $attr;
	}
	
	/** Get/Set element attribute */
	public function attr(string $key, mixed $value =''){
		if(empty($value)) return $this->attr[$key];
		$this->attr[$key] = $value;
		
		return $this;
	}

	public function __toString(){
		$attr = '';

		# For array, it will loop through and interpolate key and value together 
		if(is_array($this->attr))
			foreach($this->attr as $k=>$v)
				$attr.= "$k='$v'";
		# For string, as long as the string looks like a URL parameter, it will replace '&' to ' '
		else
			$attr = str_replace('&', ' ', $this->attr);

		# Tags that doesn't require closing.
		$tag = [
			'area', 'base', 'br', 'col',
			'command','embed','hr','img',
			'input', 'keygen', 'link', 'meta',
			'param','source','track','wbr'
		];

		if(!in_array($this->tag, $tag))
			return "<$this->tag $attr>$this->text</$this->tag>";
		else
			return "<$this->tag $attr />";
	}

	/**
	 * Creates a HTML Tag 
	 *
	 * @param string $tag
	 * @param string $text
	 * @param array $attr
	 * @return Element
	 */
	public static function tag(string $tag, string|null $text = '', array $attr = []): Element{
		return new self($tag, $text, $attr);
	}

}
