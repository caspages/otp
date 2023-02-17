import { Plugin } from 'ckeditor5/src/core';
import { findAttributeRange } from 'ckeditor5/src/typing';
import { additionalFormElements} from './utils';

export default class EditorAdvancedLinkEditing extends Plugin {

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'EditorAdvancedLinkEditing';
  }

  init() {
    const editorAdvancedLinkConfig = this.editor.config.get('editorAdvancedLink');

    if (!editorAdvancedLinkConfig.options) {
      this.enabledModelNames = [];
      return;
    }
    const enabledViewAttributes = Object.values(editorAdvancedLinkConfig.options);

    this.enabledModelNames = Object.keys(additionalFormElements).filter((modelName) => {
      return enabledViewAttributes.includes(additionalFormElements[modelName].viewAttribute);
    });
    this.enabledModelNames.forEach((modelName) => {
      this._allowAndConvertExtraAttribute(modelName, additionalFormElements[modelName].viewAttribute);
      this._removeExtraAttributeOnUnlinkCommandExecute(modelName);
      this._refreshExtraAttributeValue(modelName);
    });

    this._addExtraAttributeOnLinkCommandExecute(Object.keys(additionalFormElements));
  }

  _allowAndConvertExtraAttribute(modelName, viewName) {
    const editor = this.editor;

    editor.model.schema.extend( '$text', { allowAttributes: modelName } );

    // Model -> View (DOM)
    editor.conversion.for( 'downcast' ).attributeToElement( {
      model: modelName,
      view: ( value, { writer } ) => {
        const linkViewElement = writer.createAttributeElement( 'a', {
          [ viewName ]: value
        }, { priority: 5 } );

        // Without it the isLinkElement() will not recognize the link and the UI will not show up
        // when the user clicks a link.
        writer.setCustomProperty( 'link', true, linkViewElement );

        return linkViewElement;
      }
    } );

    // View (DOM/DATA) -> Model
    editor.conversion.for( 'upcast' )
      .elementToAttribute( {
        view: {
          name: 'a',
          attributes: {
            [ viewName ]: true
          }
        },
        model: {
          key: modelName,
          value: viewElement => viewElement.getAttribute( viewName )
        }
      } );
  }

  _addExtraAttributeOnLinkCommandExecute(modelNames) {
    const editor = this.editor;
    const linkCommand = editor.commands.get( 'link' );
    let linkCommandExecuting = false;

    linkCommand.on( 'execute', ( evt, args ) => {
      // Custom handling is only required if an extra attribute was passed into
      // editor.execute( 'link', ... ).
      if (args.length < 3) {
        return;
      }
      if (linkCommandExecuting) {
        linkCommandExecuting = false;
        return;
      }

      // If the additional attribute was passed, we stop the default execution
      // of the LinkCommand. We're going to create Model#change() block for undo
      // and execute the LinkCommand together with setting the extra attribute.
      evt.stop();
      // Prevent infinite recursion by keeping records of when link command is
      // being executed by this function.
      linkCommandExecuting = true;
      const extraAttributeValues = args[args.length - 1];
      const model = this.editor.model;
      const selection = model.document.selection;

      // Wrapping the original command execution in a model.change() block to make sure there's a single undo step
      // when the extra attribute is added.
      model.change( writer => {
        editor.execute('link', ...args);

        const firstPosition = selection.getFirstPosition();

        modelNames.forEach((modelName) => {
          if (selection.isCollapsed) {
            const node = firstPosition.textNode || firstPosition.nodeBefore;

            if (extraAttributeValues[modelName]) {
              writer.setAttribute(modelName, extraAttributeValues[modelName], writer.createRangeOn(node));
            } else {
              writer.removeAttribute(modelName, writer.createRangeOn(node));
            }

            writer.removeSelectionAttribute(modelName);
          } else {
            const ranges = model.schema.getValidRanges(selection.getRanges(), modelName);

            for (const range of ranges) {
              if (extraAttributeValues[modelName]) {
                writer.setAttribute(modelName, extraAttributeValues[modelName], range);
              } else {
                writer.removeAttribute(modelName, range);
              }
            }
          }
        });
      } );
    }, { priority: 'high' } );
  }

  _removeExtraAttributeOnUnlinkCommandExecute(modelName) {
    const editor = this.editor;
    const unlinkCommand = editor.commands.get( 'unlink' );
    const model = this.editor.model;
    const selection = model.document.selection;

    let isUnlinkingInProgress = false;

    // Make sure all changes are in a single undo step so cancel the original unlink first in the high priority.
    unlinkCommand.on( 'execute', evt => {
      if ( isUnlinkingInProgress ) {
        return;
      }

      evt.stop();

      // This single block wraps all changes that should be in a single undo step.
      model.change( () => {
        // Now, in this single "undo block" let the unlink command flow naturally.
        isUnlinkingInProgress = true;

        // Do the unlinking within a single undo step.
        editor.execute( 'unlink' );

        // Let's make sure the next unlinking will also be handled.
        isUnlinkingInProgress = false;

        // The actual integration that removes the extra attribute.
        model.change( writer => {
          // Get ranges to unlink.
          let ranges;

          if ( selection.isCollapsed ) {
            ranges = [ findAttributeRange(
              selection.getFirstPosition(),
              modelName,
              selection.getAttribute( modelName ),
              model
            ) ];
          } else {
            ranges = model.schema.getValidRanges( selection.getRanges(), modelName );
          }

          // Remove the extra attribute from specified ranges.
          for ( const range of ranges ) {
            writer.removeAttribute( modelName, range );
          }
        } );
      } );
    }, { priority: 'high' } );
  }

  _refreshExtraAttributeValue(modelName) {
    const editor = this.editor;
    const linkCommand = editor.commands.get( 'link' );
    const model = this.editor.model;
    const selection = model.document.selection;

    linkCommand.set( modelName, null );

    model.document.on( 'change', () => {
      linkCommand[ modelName ] = selection.getAttribute( modelName );
    } );
  }
}
