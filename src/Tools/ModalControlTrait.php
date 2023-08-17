<?php declare(strict_types=1);

/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

namespace JuniWalk\Form\Tools;

use JuniWalk\Form\AbstractForm;
use JuniWalk\Utils\Strings;
use Nette\Application\UI\Control;

trait ModalControlTrait
{
	public function openModal(Control|string $control, array $params = []): void
	{
		if (is_string($control) && !Strings::startsWith($control, '#')) {
			$control = $this->getComponent($control, true);
		}

		if ($control instanceof Control) {
			if ($control instanceof AbstractForm) {
				$control->setModalOpen(true);
			}

			$control = '#'.$control->getName();
		}

		$template = $this->getTemplate();
		$template->setParameters($params + [
			'openModal' => $control,
		]);

		$this->redrawControl('modals');
		$this->redirectAjax('this');
	}


	public function redirectAjax(string $dest, mixed ...$args): void
	{
		if (!$this->isAjax()) {
			$this->redirect($dest, ...$args);
		}

		$this->payload->postGet = true;
		$this->payload->url = $this->link($dest, ...$args);
	}
}
