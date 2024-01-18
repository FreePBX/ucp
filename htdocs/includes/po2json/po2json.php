<?php
/**
 * translate po file to je file obejct
 * @author Anakeen
 * @license http://creativecommons.org/licenses/by-nc-sa/2.0/fr/ Anakeen - licence CC
 */
#[\AllowDynamicProperties]
class po2json {
	public $entry = [];
	private string $encoding = 'utf-8';
	private string $lang = 'en';
	private string $pluralForms = 'nplurals=2; plural=n != 1;';

	function __construct(public $pofile, $domain = "messages") {
		$this->domain = $domain;
	}

	private function parseEntry(&$out) {
		$out = preg_replace_callback('/(?m)\[BLOCK\s*([^\]]*)\](.*?)\[ENDBLOCK\s*\1\]/s', fn ($matches) => $this->memoEntry($matches[1], $matches[2]), (string) $out);
	}

	private function trimquote($s) {
		return trim((string) $s, '"');
	}

	public function memoEntry($key, $text) {
		$tkey  = explode("\n", (string) $key);
		$ttext = explode("\n", "$text");
		$key   = trim(implode("\n", array_map($this->trimquote(...), $tkey)));
		$text  = trim(implode("\n", array_map($this->trimquote(...), $ttext)));
		if ($key && $text) {
			$this->entry[$key] = [ null, $text ];
		}
		else if ($key == "") {
			if (stristr($text, "charset=ISO-8859") !== false) {
				$this->encoding = 'iso';
			}
			if (preg_match('/Language: (.*)/m', $text, $matches)) {
				$this->lang = trim(str_replace("\\n", "", $matches[1]));
			}
			if (preg_match('/Plural-Forms: (.*)/m', $text, $matches)) {
				$this->pluralForms = trim(str_replace("\\n", "", $matches[1]));
			}
		}
	}

	private function process() {
		if (file_exists($this->pofile)) {
			$pocontent = file_get_contents($this->pofile);
			if ($pocontent) {
				$pocontent .= "\n\n";
				preg_match_all('/^msgid (?P<msgid>".*?)msgstr (?P<msgstr>".*?")\n\n/ms', $pocontent, $matches, PREG_SET_ORDER);
				foreach ($matches as $m) {
					$this->memoEntry($m['msgid'], $m['msgstr']);
				}
			}
		}
	}

	private function finalize() {
		$this->entry[""] = [ "domain" => $this->domain, "lang" => $this->lang, "plural_forms" => $this->pluralForms ];
	}

	public function po2json() {
		$this->process();
		if (count($this->entry) > 0) {
			$this->finalize();
			$js = json_encode($this->entry, JSON_THROW_ON_ERROR);
			if ($this->encoding == "iso") {
				$js = utf8_encode($js);
			}
			return $js;
		}
		else {
			return "";
		}
	}

	public function po2array() {
		if(!empty($this->po2json()) && $this->po2json() !='') {
			return json_decode((string) $this->po2json(), true, 512, JSON_THROW_ON_ERROR);
		} else {
			return [];
		}
	}
}