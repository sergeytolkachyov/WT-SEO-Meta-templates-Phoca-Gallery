<?php
/**
 * @package     WT SEO Meta Templates
 * @subpackage  WT SEO Meta Templates - Phoca Gallrey
 * @version     2.0.0
 * @Author      Sergey Tolkachyov, https://web-tolk.ru
 * @copyright   Copyright (C) 2022 Sergey Tolkachyov
 * @license     GNU/GPL http://www.gnu.org/licenses/gpl-2.0.html
 * @since       1.0.0
 */

namespace Joomla\Plugin\System\Wt_seo_meta_templates_phoca_gallery\Extension;
// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Profiler\Profiler;
use Joomla\Event\SubscriberInterface;

final class Wt_seo_meta_templates_phoca_gallery extends CMSPlugin implements SubscriberInterface
{
	protected $autoloadLanguage = true;
	protected $allowLegacyListeners = false;

	/**
	 *
	 * @return array
	 *
	 * @throws \Exception
	 * @since 2.0.0
	 *
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onWt_seo_meta_templatesAddVariables' => 'onWt_seo_meta_templatesAddVariables'
		];
	}

	public function onWt_seo_meta_templatesAddVariables($event) : void
	{


		!JDEBUG ?: Profiler::getInstance('Application')->mark('<strong>plg WT SEO Meta templates - Phoca Gallery provider plugin</strong>: start');
		$app    = $this->getApplication();
		$option = $app->getInput()->get('option');
		if ($option == 'com_phocagallery')
		{
			!JDEBUG ?: Profiler::getInstance('Application')->mark('<strong>plg WT SEO Meta templates - Phoca Gallery provider plugin</strong>: After load Phoca Gallery config');

			$variables = array();
			// Short codes for Phoca Gallery category view
			if ($app->getInput()->get('view') == 'category')
			{
				$id = $app->getInput()->getInt('id');

				BaseDatabaseModel::addIncludePath(JPATH_SITE . 'components/com_phocagallery/models', 'PhocagalleryModel');
				$model    = BaseDatabaseModel::getInstance('Category', 'PhocagalleryModel');
				$category = $model->getCategory();

				/*
				 * Phoca Gallery category variables for short codes
				 */

				$variables[] = [
					'variable' => 'PHOCA_G_CATEGORY_NAME',
					'value'    => $category->title,
				];
				//Phoca Gallery category id
				$variables[] = [
					'variable' => 'PHOCA_G_CATEGORY_ID',
					'value'    => $category->id,
				];

				/**
				 * @paramm  category id
				 * @param   1 - published
				 *
				 * @return  object
				 */
				$category_count_images_obj = $model->getCountImages($category->id, 1);
				if ($category_count_images_obj->countimg > 0)
				{
					$category_count_images = $category_count_images_obj->countimg;
				}
				else
				{
					$category_count_images = '';
				}
				$variables[] = [
					'variable' => 'PHOCA_G_CATEGORY_COUNT_IMAGES',
					'value'    => $category_count_images,
				];


				$variables[] = [
					'variable' => 'PHOCA_G_CATEGORY_GEOTITLE',
					'value'    => $category->geotitle,
				];

				if ($category->parent_id != 0)
				{
					$parent_category = $model->getParentCategory();
					$variables[] = [
						'variable' => 'PHOCA_G_PARENT_CATEGORY_NAME',
						'value'    => $parent_category->title,
					];
				}

				//Массив для тайтлов и дескрипшнов по формуле для передачи в основной плагин
				$seo_meta_template = array();

				/*
				 * Если включена глобальная перезапись <title> категории. Все по формуле.
				 */
				if ($this->params->get('show_debug') == 1)
				{
					$this->prepareDebugInfo('','<h4>WT SEO Meta templates - Phoca Gallery provider plugin debug</h4>');
					$this->prepareDebugInfo('','<p><strong>Phoca Gallery Title</strong>: ' . $category->title . '</p>');
					$this->prepareDebugInfo('','<p><strong>Phoca Gallery Meta desc:</strong> ' . $category->metadesc . '</p>');
				}


				$category_title_category_exclude = $this->params->get('phoca_gallery_category_title_category_exclude');
				if (!is_array($category_title_category_exclude))
				{
					$category_title_category_exclude = array();
				}

				if ($this->params->get('global_phoca_gallery_category_title_replace') == 1 && !in_array($category->id, $category_title_category_exclude))
				{

					/*
					 * Если переписываем только пустые. Там, где пустое
					 * $category->metadesc;
					 */

					if ($this->params->get('global_phoca_gallery_category_title_replace_only_empty') == 1)
					{
						if ($this->params->get('show_debug') == 1)
						{
							$this->prepareDebugInfo('','<p>' . Text::_('PLG_WT_SEO_META_TEMPLATES_PHOCA_GALLERY_DEBUG_GLOBAL_CATEGORY_TITLE_REPLACE_ONLY_EMPTY') . '</p>');
						}
						if (empty($category->title) == true)
						{
							if ($this->params->get('show_debug') == 1)
							{
								$this->prepareDebugInfo('','<p>' . Text::_('PLG_WT_SEO_META_TEMPLATES_PHOCA_GALLERY_DEBUG_EMPTY_TITLE_FOUND') . '</p>');
							}
							$title_template             = $this->params->get('phoca_gallery_category_title_template');
							$seo_meta_template['title'] = $title_template;
						}
					}
					else
					{
						//Переписываем все глобально
						if ($this->params->get('show_debug') == 1)
						{
							$this->prepareDebugInfo('','<p>' . Text::_('PLG_WT_SEO_META_TEMPLATES_PHOCA_GALLERY_DEBUG_GLOBAL_CATEGORY_TITLE_REPLACE') . '</p>');
						}
						$title_template             = $this->params->get('phoca_gallery_category_title_template');
						$seo_meta_template['title'] = $title_template;
					}

				}

				/*
				 * Если включена глобальная перезапись description категории. Все по формуле.
				 */

				$category_metadesc_category_exclude = $this->params->get('phoca_gallery_category_metadesc_category_exclude');
				if (!is_array($category_metadesc_category_exclude))
				{
					$category_metadesc_category_exclude = array();
				}

				if ($this->params->get('global_phoca_gallery_category_description_replace') == 1 && !in_array($category->id, $category_metadesc_category_exclude))
				{

					/*
					 * Если переписываем только пустые. Там, где пустое
					 * $category->{'meta_description_'.$current_lang}
					 */

					if ($this->params->get('global_phoca_gallery_category_description_replace_only_empty') == 1)
					{
						if ($this->params->get('show_debug') == 1)
						{
							$this->prepareDebugInfo('','<p>' . Text::_('PLG_WT_SEO_META_TEMPLATES_PHOCA_GALLERY_DEBUG_GLOBAL_CATEGORY_META_DESCRIPTION_REPLACE_ONLY_EMPTY') . '</p>');
						}

						if (empty($category->metadesc) == true)
						{
							if ($this->params->get('show_debug') == 1)
							{
								$this->prepareDebugInfo('','<p>' . Text::_('PLG_WT_SEO_META_TEMPLATES_PHOCA_GALLERY_DEBUG_EMPTY_META_DESCRIPTION_FOUND') . '</p>');
							}
							$description_template             = $this->params->get('phoca_gallery_category_meta_description_template');
							$seo_meta_template['description'] = $description_template;
						}
					}
					else
					{
						//Переписываем все глобально
						if ($this->params->get('show_debug') == 1)
						{
							$this->prepareDebugInfo('','<p>' . Text::_('PLG_WT_SEO_META_TEMPLATES_PHOCA_GALLERY_DEBUG_GLOBAL_CATEGORY_META_DESCRIPTION_REPLACE') . '</p>');
						}
						$description_template             = $this->params->get('phoca_gallery_category_meta_description_template');
						$seo_meta_template['description'] = $description_template;
					}
				}


				/*
				 * Добавляем или нет суффикс к title и meta-description страницы
				 * для страниц пагинации.
				 */

				//$limitstart - признак страницы пагинации, текущая страница пагинации
				$limitstart = $app->getInput()->get('limitstart');
				if (isset($limitstart) && (int) $limitstart > 0)
				{

					if ($this->params->get('enable_page_title_and_metadesc_pagination_suffix') == 1)
					{
						$pagination                  = $model->getPagination();
						$current_pagination_page_num = $pagination->pagesCurrent;

						if (!empty($this->params->get('page_title_pagination_suffix_text')))
						{
							// Тексты суффиксов из параметров плагина
							$pagination_suffix_title = sprintf(Text::_($this->params->get('page_title_pagination_suffix_text')), $current_pagination_page_num);
							// Суффиксы для страниц пагинации - "- страница NNN".
							if (!empty($seo_meta_template['title']) && !empty($pagination_suffix_title))
							{
								$seo_meta_template['title'] = $seo_meta_template['title'] . ' ' . $pagination_suffix_title;
							}
							elseif (!empty($pagination_suffix_title))
							{
								//Если шаблоны отключены - просто добавляем суффиксы в пагинацию
								$seo_meta_template['title'] = $category->title . ' ' . $pagination_suffix_title;
							}

						}

						if (!empty($this->params->get('page_metadesc_pagination_suffix_text')))
						{

							$pagination_suffix_metadesc = sprintf(Text::_($this->params->get('page_metadesc_pagination_suffix_text')), $current_pagination_page_num);

							// Суффиксы для страниц пагинации - "- страница NNN".
							if (!empty($seo_meta_template['description']) && !empty($pagination_suffix_metadesc))
							{
								$seo_meta_template['description'] = $seo_meta_template['description'] . ' ' . $pagination_suffix_metadesc;
							}
							elseif (!empty($pagination_suffix_metadesc))
							{
								//Если шаблоны отключены - просто добавляем суффиксы в пагинацию
								$seo_meta_template['description'] = $category->metadesc . ' ' . $pagination_suffix_metadesc;
							}
						}
					}

				}

			}


			$data = [
				'variables'          => $variables,
				'seo_tags_templates' => $seo_meta_template,
			];


			$this->prepareDebugInfo('SEO variables',$data);

			!JDEBUG ?: Profiler::getInstance('Application')->mark('<strong>plg WT SEO Meta templates - Phoca Gallery provider plugin</strong>: Before return data. End.');

			$event->setArgument('result',$data);
		}
	}

	/**
	 * Prepare html output for debug info from main function
	 *
	 * @param $debug_section_header string
	 * @param $debug_data           string|array
	 *
	 * @return void
	 * @since 2.0.0
	 */
	private function prepareDebugInfo($debug_section_header, $debug_data): void
	{
		if ($this->params->get('show_debug') == 1)
		{
			$session      = $this->getApplication()->getSession();
			$debug_output = $session->get("wtseometatemplatesdebugoutput");
			if (!empty($debug_section_header))
			{
				$debug_output .= "<details style='border:1px solid #0FA2E6; margin-bottom:5px;'>";
				$debug_output .= "<summary style='background-color:#384148; color:#fff; padding:10px;'>" . $debug_section_header . "</summary>";
			}

			if (is_array($debug_data) || is_object($debug_data))
			{
				$debug_data   = print_r($debug_data, true);
				$debug_output .= "<pre style='background-color: #eee; padding:10px;'>";
			}

			$debug_output .= $debug_data;
			if (is_array($debug_data) || is_object($debug_data))
			{
				$debug_output .= "</pre>";
			}
			if (!empty($debug_section_header))
			{
				$debug_output .= "</details>";
			}
			$session->set("wtseometatemplatesdebugoutput", $debug_output);
		}
	}
}//plgSystemWt_seo_meta_templates_Phoca Gallery