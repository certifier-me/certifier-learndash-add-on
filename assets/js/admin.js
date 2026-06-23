( function () {
	function replaceTemplateIndex( html, index ) {
		return html.split( '__index__' ).join( String( index ) );
	}

	function getNextIndex( table ) {
		var index = Number( table.getAttribute( 'data-certifier-next-index' ) || 0 );
		table.setAttribute( 'data-certifier-next-index', String( index + 1 ) );

		return index;
	}

	document.addEventListener( 'click', function ( event ) {
		var addButton = event.target.closest( '[data-certifier-add-mapping]' );
		if ( addButton ) {
			var table = document.querySelector( '[data-certifier-mappings]' );
			var rows = document.querySelector( '[data-certifier-mapping-rows]' );
			var template = document.querySelector( '[data-certifier-mapping-template]' );

			if ( ! table || ! rows || ! template ) {
				return;
			}

			rows.insertAdjacentHTML( 'beforeend', replaceTemplateIndex( template.innerHTML, getNextIndex( table ) ) );
			return;
		}

		var removeButton = event.target.closest( '[data-certifier-remove-mapping]' );
		if ( removeButton ) {
			var row = removeButton.closest( '[data-certifier-mapping-row]' );
			if ( row ) {
				row.remove();
			}
		}
	} );
}() );
