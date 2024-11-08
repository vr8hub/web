<?
class EbookTag extends Tag{
	public function __construct(){
		$this->Type = TagType::Ebook;
	}

	// *******
	// GETTERS
	// *******
	protected function GetUrlName(): string{
		if($this->_UrlName === null){
			$this->_UrlName = Formatter::MakeUrlSafe($this->Name);
		}

		return $this->_UrlName;
	}

	protected function GetUrl(): string{
		if($this->_Url === null){
			$this->_Url = '/subjects/' . $this->UrlName;
		}

		return $this->_Url;
	}

	// *******
	// METHODS
	// *******

	/**
	 * @throws Exceptions\ValidationException
	 */
	public function Validate(): void{
		$error = new Exceptions\ValidationException();

		if(isset($this->Name)){
			$this->Name = trim($this->Name);

			if($this->Name == ''){
				$error->Add(new Exceptions\EbookTagNameRequiredException());
			}

			if(strlen($this->Name) > EBOOKS_MAX_STRING_LENGTH){
				$error->Add(new Exceptions\StringTooLongException('Ebook tag: '. $this->Name));
			}
		}
		else{
			$error->Add(new Exceptions\EbookTagNameRequiredException());
		}

		if($this->Type != TagType::Ebook){
			$error->Add(new Exceptions\InvalidEbookTagTypeException($this->Type));
		}

		if($error->HasExceptions){
			throw $error;
		}
	}

	/**
	 * @throws Exceptions\ValidationException
	 */
	public function Create(): void{
		$this->Validate();

		Db::Query('
			INSERT into Tags (Name, UrlName, Type)
			values (?,
				?,
				?)
		', [$this->Name, $this->UrlName, $this->Type]);
		$this->TagId = Db::GetLastInsertedId();
	}

	/**
	 * @throws Exceptions\ValidationException
	 */
	public function GetByNameOrCreate(string $name): EbookTag{
		$result = Db::Query('
				SELECT *
				from Tags
				where Name = ?
					and Type = ?
			', [$name, TagType::Ebook], EbookTag::class);

		if(isset($result[0])){
			return $result[0];
		}
		else{
			$this->Create();
			return $this;
		}
	}
}
