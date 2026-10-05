/**
 * Primary term select under each enabled taxonomy's panel in the block editor.
 */

/**
 * WordPress dependencies
 */
import { SelectControl } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { decodeEntities } from '@wordpress/html-entities';
import { __, sprintf } from '@wordpress/i18n';

const taxonomies = window.vgptTaxonomies || {};
const EMPTY = [];
const FIELDS = { _fields: 'id,name,parent', context: 'view' };

/**
 * Builds "Parent › Child" paths for the checked terms, sorted by name like get_the_terms().
 *
 * @param {Object[]} terms   Checked terms.
 * @param {Object}   parents Ancestor terms keyed by ID.
 * @return {Object[]} Terms with `id`, `label`, and `depth`.
 */
function getPaths( terms, parents ) {
	return [ ...terms ]
		.sort( ( a, b ) => a.name.localeCompare( b.name ) )
		.map( ( term ) => {
			const names = [];

			for ( let t = term; t; t = parents[ t.parent ] ) {
				names.unshift( decodeEntities( t.name ) );
			}

			return {
				id: term.id,
				label: names.join( ' › ' ),
				depth: names.length,
			};
		} );
}

function PrimaryTermSelect( { slug } ) {
	const { singular, metaKey } = taxonomies[ slug ];

	const { isReady, isHierarchical, termIds, terms, parents, primary } =
		useSelect(
			( select ) => {
				const { getTaxonomy, getEntityRecords, getEntityRecord } =
					select( coreStore );
				const { getEditedPostAttribute } = select( editorStore );
				const taxonomy = getTaxonomy( slug );
				const ids = taxonomy
					? getEditedPostAttribute( taxonomy.rest_base ) || EMPTY
					: EMPTY;

				// Queried by ID, so a term added from the panel shows up right away.
				const checked = ids.length
					? getEntityRecords( 'taxonomy', slug, {
							...FIELDS,
							include: ids,
							per_page: -1,
						} ) || EMPTY
					: EMPTY;

				const ancestors = {};

				for ( const term of checked ) {
					for (
						let parent = term.parent;
						parent && ! ancestors[ parent ];
						parent = ancestors[ parent ]?.parent
					) {
						const record = getEntityRecord(
							'taxonomy',
							slug,
							parent,
							FIELDS
						);

						if ( ! record ) {
							break;
						}

						ancestors[ parent ] = record;
					}
				}

				return {
					isReady: !! taxonomy,
					isHierarchical: !! taxonomy?.hierarchical,
					termIds: ids,
					terms: checked,
					parents: ancestors,
					primary: getEditedPostAttribute( 'meta' )?.[ metaKey ] || 0,
				};
			},
			[ slug, metaKey ]
		);

	const { editPost } = useDispatch( editorStore );
	const isChecked = termIds.includes( primary );

	// Clear a primary term that's been unchecked. Waits for the taxonomy so loading never dirties the post.
	useEffect( () => {
		if ( isReady && primary && ! isChecked ) {
			editPost( { meta: { [ metaKey ]: 0 } } );
		}
	}, [ isReady, primary, isChecked, metaKey, editPost ] );

	const options = getPaths(
		terms.filter( ( term ) => termIds.includes( term.id ) ),
		parents
	);

	if ( termIds.length < 2 || options.length < 2 ) {
		return null;
	}

	// Matches Core::get_primary_term()'s fallback: the deepest term, or the first when flat.
	const fallback = isHierarchical
		? options.reduce( ( a, b ) => ( b.depth > a.depth ? b : a ) )
		: options[ 0 ];

	return (
		<div className="vgpt-select" style={ { marginTop: '16px' } }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ sprintf(
					/* translators: %s: Taxonomy singular name, e.g. "Category". */
					__( 'Primary %s', 'viget-primary-term' ),
					singular
				) }
				value={ isChecked ? primary : 0 }
				options={ [
					{
						value: 0,
						label: sprintf(
							/* translators: %s: Term path, e.g. "Chains › Roller". */
							__( 'Default: %s', 'viget-primary-term' ),
							fallback.label
						),
					},
					...options.map( ( term ) => ( {
						value: term.id,
						label: term.label,
					} ) ),
				] }
				onChange={ ( value ) =>
					editPost( {
						meta: { [ metaKey ]: parseInt( value, 10 ) || 0 },
					} )
				}
			/>
		</div>
	);
}

const withPrimaryTermSelect = createHigherOrderComponent(
	( TermSelector ) => ( props ) => {
		if ( ! taxonomies[ props.slug ] ) {
			return <TermSelector { ...props } />;
		}

		return (
			<>
				<TermSelector { ...props } />
				<PrimaryTermSelect slug={ props.slug } />
			</>
		);
	},
	'withPrimaryTermSelect'
);

addFilter(
	'editor.PostTaxonomyType',
	'viget-primary-term/primary-term-select',
	withPrimaryTermSelect
);
