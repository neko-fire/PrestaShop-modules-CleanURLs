<?php
class Link extends LinkCore
{
	/**
	 * Create a link to a category
	 *
	 * @param mixed $category Category object (can be an ID category, but deprecated)
	 * @param string $alias
	 * @param int $id_lang
	 * @param string $selected_filters Url parameter to autocheck filters of the module blocklayered
	 * @return string
	 */
	 //MODIFIED BY LAPY90
	public function getCategoryLink($category, $alias = NULL, $id_lang = NULL, $selected_filters = NULL, $id_shop = NULL)
	{
	
		$dispatcher = Dispatcher::getInstance();
		
		if (!$id_lang)
			$id_lang = Context::getContext()->language->id;
		$url = _PS_BASE_URL_.__PS_BASE_URI__.$this->getLangLink($id_lang);

		if (!is_object($category))
			$category = new Category($category, $id_lang);

		// Set available keywords
		$params = array();
		$params['id'] = $category->id;
		$params['rewrite'] = (!$alias) ? $category->link_rewrite : $alias;
		$params['meta_keywords'] =	Tools::str2url($category->meta_keywords);
		$params['meta_title'] = Tools::str2url($category->meta_title);

		// Selected filters is used by the module blocklayered
		$selected_filters = is_null($selected_filters) ? '' : $selected_filters;

		if (empty($selected_filters))
			$rule = 'category_rule';
		else
		{
			$rule = 'layered_rule';
			$params['selected_filters'] = $selected_filters;
		}

		
	
		if ($dispatcher->hasKeyword('category_rule', $id_lang, 'parent_categories'))
		{
			//RETRIEVING ALL THE PARENT CATEGORIES
			$cats = array();
			foreach ($category->getParentsCategories() as $cat)
				if (!in_array($cat['id_category'], array(1, 2, $category->id)))//remove root, home and current category from the URL
					$cats[] = $cat['link_rewrite']; //THE CATEGORIES ARE BEING ASSIGNED IN THE WRONG ORDER (?)
			$params['parent_categories'] = implode('/', array_reverse($cats));//ADD THE URL SLASHES TO THE CATEGORIES IN REVERSE ORDER
		}
		
		return $url.Dispatcher::getInstance()->createUrl($rule, $id_lang, $params, $this->allow);

	}

	
	/**
	 * Get pagination link
	 *
	 * @param string $type Controller name
	 * @param int $id_object
	 * @param boolean $nb Show nb element per page attribute
	 * @param boolean $sort Show sort attribute
	 * @param boolean $pagination Show page number attribute
	 * @param boolean $array If false return an url, if true return an array
	 */
	public function getPaginationLink($type, $id_object, $nb = false, $sort = false, $pagination = false, $array = false)
	{
		// If no parameter $type, try to get it by using the controller name
		if (!$type && !$id_object)
		{
			$method_name = 'get'.Dispatcher::getInstance()->getController().'Link';
			if (method_exists($this, $method_name) && isset($_GET['id_'.Dispatcher::getInstance()->getController()]))
			{
				$type = Dispatcher::getInstance()->getController();
				$id_object = $_GET['id_'.$type];
			}
		}

		if ($type && $id_object)
			$url = $this->{'get'.$type.'Link'}($id_object, null);
		else
		{
			if (isset(Context::getContext()->controller->php_self))
				$name = Context::getContext()->controller->php_self;
			else
				$name = Dispatcher::getInstance()->getController();
			$url = $this->getPageLink($name);
		}

		$vars = array();
		$vars_nb = array('n', 'search_query');
		$vars_sort = array('orderby', 'orderway');
		$vars_pagination = array('p');

		foreach ($_GET as $k => $value)
		{
			// Ha!*!*y strip var like category_rewrite from url
			if ($k != 'id_'.$type && $k != $type.'_rewrite' && $k != 'controller')
			{
				if (Configuration::get('PS_REWRITING_SETTINGS') && ($k == 'isolang' || $k == 'id_lang'))
					continue;
				$if_nb = (!$nb || ($nb && !in_array($k, $vars_nb)));
				$if_sort = (!$sort || ($sort && !in_array($k, $vars_sort)));
				$if_pagination = (!$pagination || ($pagination && !in_array($k, $vars_pagination)));
				if ($if_nb && $if_sort && $if_pagination)
				{
					if (!is_array($value))
						$vars[urlencode($k)] = $value;
					else
					{
						foreach (explode('&', http_build_query(array($k => $value), '', '&')) as $key => $val)
						{
							$data = explode('=', $val);
							$vars[urldecode($data[0])] = $data[1];
						}
					}
				}
			}
		}

		if (!$array)
			if (count($vars))
				return $url.(($this->allow == 1 || $url == $this->url) ? '?' : '&').http_build_query($vars, '', '&');
			else
				return $url;
		
		$vars['requestUrl'] = $url;

		if ($type && $id_object)
			$vars['id_'.$type] = (is_object($id_object) ? (int)$id_object->id : (int)$id_object);
			
		if (!$this->allow == 1)
			$vars['controller'] = Dispatcher::getInstance()->getController();
		return $vars;
	}

}
