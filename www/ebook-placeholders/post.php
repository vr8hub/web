<?

try{
	session_start();
	$httpMethod = HttpInput::ValidateRequestMethod([Enums\HttpMethod::Post]);
	$exceptionRedirectUrl = '/ebook-placeholders/new';

	if(Session::$User === null){
		throw new Exceptions\LoginRequiredException();
	}

	// POSTing a new ebook placeholder.
	if($httpMethod == Enums\HttpMethod::Post){
		if(!Session::$User->Benefits->CanEditEbookPlaceholders){
			throw new Exceptions\InvalidPermissionsException();
		}

		$ebook = new Ebook();

		$title = HttpInput::Str(POST, 'ebook-title');
		if(isset($title)){
			$ebook->Title = $title;
		}

		$authors = [];
		$authorFields = ['author-name-1', 'author-name-2', 'author-name-3'];
		foreach($authorFields as $authorField){
			$authorName = HttpInput::Str(POST, $authorField);
			if(!isset($authorName)){
				continue;
			}
			$author = new Contributor();
			$author->Name = $authorName;
			$author->UrlName = Formatter::MakeUrlSafe($author->Name);
			$author->MarcRole = Enums\MarcRole::Author;
			$authors[] = $author;
		}
		$ebook->Authors = $authors;

		$translators = [];
		$translatorFields = ['translator-name-1', 'translator-name-2'];
		foreach($translatorFields as $translatorField){
			$translatorName = HttpInput::Str(POST, $translatorField);
			if(!isset($translatorName)){
				continue;
			}
			$translator = new Contributor();
			$translator->Name = $translatorName;
			$translator->UrlName = Formatter::MakeUrlSafe($translator->Name);
			$translator->MarcRole = Enums\MarcRole::Translator;
			$translators[] = $translator;
		}
		$ebook->Translators = $translators;

		$collectionMemberships = [];
		$collectionNameFields = ['collection-name-1', 'collection-name-2', 'collection-name-3'];
		foreach($collectionNameFields as $collectionNameField){
			$collectionName = HttpInput::Str(POST, $collectionNameField);
			if(!isset($collectionName)){
				continue;
			}
			$collectionSequenceNumber = HttpInput::Int(POST, 'sequence-number-' . $collectionNameField);
			$collection = Collection::FromName($collectionName);
			$collection->Type = Enums\CollectionType::tryFrom(HttpInput::Str(POST, 'type-' . $collectionNameField) ?? '');

			$cm = new CollectionMembership();
			$cm->Collection = $collection;
			$cm->SequenceNumber = $collectionSequenceNumber;
			$collectionMemberships[] = $cm;
		}
		$ebook->CollectionMemberships = $collectionMemberships;

		$ebookPlaceholder = new EbookPlaceholder();
		$ebookPlaceholder->FillFromHttpPost();
		$ebook->EbookPlaceholder = $ebookPlaceholder;

		// Do we have a `Project` to create at the same time?
		$project = null;
		if($ebookPlaceholder->IsInProgress){
			$project = new Project();
			$project->FillFromHttpPost();
			$project->Started = NOW;
			$project->EbookId = 0; // Dummy value to pass validation, we'll set it to the real value before creating the `Project`.
			$project->Validate();
		}

		$ebook->FillIdentifierFromTitleAndContributors();

		// These properties must be set before calling `Ebook::Create()` to prevent the getters from triggering DB queries or accessing `Ebook::$EbookId` before it is set.
		$ebook->Tags = [];
		$ebook->LocSubjects = [];
		$ebook->Illustrators = [];
		$ebook->Contributors = [];

		try{
			$ebook->Create();
		}
		catch(Exceptions\DuplicateEbookException $ex){
			// If the identifier already exists but a `Project` was sent with this request, create the `Project` anyway.
			$existingEbook = Ebook::GetByIdentifier($ebook->Identifier);
			if($ebookPlaceholder->IsInProgress && $project !== null){
				$ebook->EbookId = $existingEbook->EbookId;
				$_SESSION['is-only-ebook-project-created'] = true;
			}
			else{
				// No `Project`, throw the exception and really fail.
				$ebook = $existingEbook;
				throw $ex;
			}
		}

		if($ebookPlaceholder->IsInProgress && $project !== null){
			$project->EbookId = $ebook->EbookId;
			$project->Ebook = $ebook;
			$project->Create();
		}

		$_SESSION['ebook'] = $ebook;
		$_SESSION['is-ebook-placeholder-created'] = true;

		http_response_code(Enums\HttpCode::SeeOther->value);
		header('Location: /ebook-placeholders/new');
	}
}
catch(Exceptions\LoginRequiredException){
	Template::RedirectToLogin();
}
catch(Exceptions\InvalidPermissionsException | Exceptions\InvalidHttpMethodException | Exceptions\HttpMethodNotAllowedException){
	Template::Emit403();
}
catch(Exceptions\AppException $ex){
	$_SESSION['ebook'] = $ebook;
	$_SESSION['exception'] = $ex;

	http_response_code(Enums\HttpCode::SeeOther->value);
	header('Location: ' . $exceptionRedirectUrl);
}
