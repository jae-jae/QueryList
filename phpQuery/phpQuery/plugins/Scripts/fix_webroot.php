<?php
$selector = 'img[src], link[href], script[src]';
$filter = ':not([href^=<?php])'
		.':not([src^=<?php])'
		.':not([href^=http://])'
		.':not([src^=http://])'
		.':not([src^=/])';
foreach($self[$selector]->filter($filter) as $el) {
	$el = pq($el, $self->getDocumentID());
	// imgs and scripts
	if ( $el->is('img') || $el->is('script') )
		$el->attr('src', $params[0].$el->attr('src'));
	// css
	if ( $el->is('link') )
		$el->attr('href', $params[0].$el->attr('href'));
}