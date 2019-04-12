import React, {createElement} from 'react'
import {PropTypes as T} from 'prop-types'
import get from 'lodash/get'

import {trans} from '#/main/app/intl/translation'
import {FormData} from '#/main/app/content/form/containers/data'
import {HtmlInput} from '#/main/app/data/types/html/components/input'
import {NumberInput} from '#/main/app/data/types/number/components/input'

import {makeId} from '#/main/core/scaffolding/id'

import {Item as ItemTypes, ItemType as ItemTypeTypes} from '#/plugin/exo/items/prop-types'
import {ItemType} from '#/plugin/exo/items/components/type'

const ItemEditor = props => {
  let supportedScores, currentScore, availableScores
  if (props.definition.answerable) {
    supportedScores = props.definition.supportScores(props.item)

    currentScore = supportedScores.find(score => score.name === get(props.item, 'score.type'))
    availableScores = supportedScores.reduce((scoreChoices, current) => Object.assign(scoreChoices, {
      [current.name]: current.meta.label
    }), {})
  }

  return (
    <FormData
      id={`form-${props.item.id}`}
      className="quiz-item item-editor"
      embedded={props.embedded}
      name={props.formName}
      meta={props.meta}
      dataPart={props.path}
      disabled={props.disabled}
      sections={[
        {
          title: trans('general'),
          primary: true,
          fields: [
            {
              name: 'type',
              label: trans('type'),
              type: 'string',
              hideLabel: true,
              displayed: props.meta,
              render: () => {
                const CurrentType = <ItemType name={props.definition.name} size="lg" />

                return CurrentType
              }
            }, {
              name: 'content',
              label: trans('question', {}, 'quiz'),
              type: 'html',
              required: true,
              displayed: props.definition.answerable
            }
          ]
        }, {
          title: trans('custom'),
          primary: true,
          render: () => {
            const CustomSection = createElement(props.definition.components.editor, {
              formName: props.formName,
              path: props.path,
              disabled: props.disabled,
              item: props.item,
              update: props.update
            })

            return CustomSection
          }
        }, {
          icon: 'fa fa-fw fa-info',
          title: trans('information'),
          fields: [
            {
              name: 'title',
              label: trans('title'),
              type: 'string'
            }, {
              name: 'description',
              label: trans('description'),
              type: 'html'
            }, {
              name: 'tags',
              label: trans('tags'),
              type: 'tag'
            }
          ]
        }, {
          icon: 'fa fa-fw fa-boxes',
          title: trans('question_objects', {}, 'quiz'),
          displayed: props.definition.answerable,
          fields: [
            {
              name: 'objects',
              label: trans('medias'),
              type: 'medias'
            }
          ]
        }, {
          icon: 'fa fa-fw fa-percentage',
          title: trans('score'),
          displayed: props.definition.answerable,
          fields: [
            {
              name: 'score.type',
              label: trans('calculation_mode', {}, 'quiz'),
              type: 'choice',
              required: true,
              options: {
                noEmpty: true,
                condensed: true,
                // get the list of score supported by the current type
                choices: availableScores
              },
              linked: currentScore ? currentScore
                // generate the list of fields for the score type
                .configure(get(props.item, 'score'))
                .map(scoreProp => Object.assign({}, scoreProp, {
                  name: `score.${scoreProp.name}`
                })) : []
            }
          ]
        }, {
          id: 'help',
          icon: 'fa fa-fw fa-info',
          title: trans('help'),
          displayed: props.definition.answerable,
          fields: [
            {
              name: 'hints',
              label: trans('hints', {}, 'quiz'),
              type: 'collection', // TODO
              options: {
                placeholder: trans('no_hint_info', {}, 'quiz'),
                button: trans('add_hint', {}, 'quiz'),
                defaultItem: {id: makeId(), penalty: 0},
                render: (hint = {}, hintErrors, hintIndex) => {
                  const HintEditor = (
                    <div className="hint-control">
                      <HtmlInput
                        id={`hint-${hintIndex}-text`}
                        className="hint-value"
                        value={hint.value}
                        size="sm"
                        onChange={value => props.update(`hints[${hintIndex}].value`, value)}
                      />

                      <NumberInput
                        id={`hint-${hintIndex}-penalty`}
                        className="hint-penalty"
                        min={0}
                        value={hint.penalty}
                        size="sm"
                        onChange={value => props.update(`hints[${hintIndex}].penalty`, value)}
                      />
                    </div>
                  )

                  return HintEditor
                }
              }
            }, {
              name: 'feedback',
              label: trans('feedback', {}, 'quiz'),
              type: 'html'
            }
          ]
        }
      ]}
    />
  )
}

ItemEditor.propTypes = {
  embedded: T.bool,
  meta: T.bool,
  formName: T.string.isRequired,
  path: T.string,
  disabled: T.bool,

  /**
   * The item object currently edited.
   */
  item: T.shape(
    ItemTypes.propTypes
  ).isRequired,

  /**
   * The definition of the item type.
   */
  definition: T.shape(
    ItemTypeTypes.propTypes
  ).isRequired,

  update: T.func.isRequired
}

ItemEditor.defaultProps = {
  embedded: false,
  meta: false,
  disabled: false
}

export {
  ItemEditor
}