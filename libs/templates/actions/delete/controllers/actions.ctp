	function <?php echo $alias; ?>($<?php echo $admin->primaryKey; ?> = null) {
		if (!empty($this->data['<?php echo $modelClass; ?>']['<?php echo $admin->primaryKey; ?>'])) {
			if ($this-><?php echo $modelClass; ?>->delete($this->data['<?php echo $modelClass; ?>']['<?php echo $admin->primaryKey; ?>'])) {
<?php if ($admin->sessions): ?>
				$this->Session->setFlash(__d('<?php echo $admin->plugin; ?>', '<?php echo ucfirst(strtolower($singularHumanName)); ?> deleted', true), 'flash/success');
				$this->redirect(array('action' => '<?php echo $admin->redirectTo; ?>'));
<?php else: ?>
				$this->flash(__d('<?php echo $admin->plugin; ?>', '<?php echo ucfirst(strtolower($singularHumanName)); ?> deleted', true), array('action' => '<?php echo $admin->redirectTo; ?>'));
<?php endif; ?>
			}
<?php if ($admin->sessions): ?>
			$this->Session->setFlash(__d('<?php echo $admin->plugin; ?>', '<?php echo ucfirst(strtolower($singularHumanName)); ?> was not deleted', true), 'flash/error');
<?php else: ?>
			$this->flash(__d('<?php echo $admin->plugin; ?>', '<?php echo ucfirst(strtolower($singularHumanName)); ?> was not deleted', true), array('action' => '<?php echo $admin->redirectTo; ?>'));
<?php endif; ?>
			$<?php echo $admin->primaryKey; ?> = $this->data['<?php echo $modelClass; ?>']['<?php echo $admin->primaryKey; ?>'];
		}

		$this->data = $this-><?php echo $modelClass; ?>->find('<?php echo $alias; ?>', array('<?php echo $admin->primaryKey; ?>' => $<?php echo $admin->primaryKey; ?>));
		if (!$this->data) {
<?php if ($admin->sessions): ?>
			$this->Session->setFlash(__d('<?php echo $admin->plugin; ?>', '<?php echo ucfirst(strtolower($singularHumanName)); ?> unspecified', true), 'flash/error');
			$this->redirect(array('action' => '<?php echo $admin->redirectTo; ?>'));
<?php else: ?>
			$this->flash(__d('<?php echo $admin->plugin; ?>', '<?php echo ucfirst(strtolower($singularHumanName)); ?> unspecified', true), array('action' => '<?php echo $admin->redirectTo; ?>'));
<?php endif; ?>
		}
	}