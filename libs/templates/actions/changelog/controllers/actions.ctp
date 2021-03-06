	function <?php echo $alias; ?>($<?php echo $admin->primaryKey; ?> = null) {
		$<?php echo $admin->primaryKey; ?> = (!$<?php echo $admin->primaryKey; ?> && !empty($this->params['named']['<?php echo $admin->primaryKey; ?>'])) ? $this->params['named']['<?php echo $admin->primaryKey; ?>'] : $<?php echo $admin->primaryKey; ?>;
		$<?php echo $singularName . Inflector::pluralize(Inflector::humanize($alias)); ?> = $this-><?php echo $modelClass; ?>->findLog(array('model_id' => $<?php echo $admin->primaryKey; ?>));

		if (!$<?php echo $singularName . Inflector::pluralize(Inflector::humanize($alias)); ?>) {
<?php if ($admin->sessions): ?>
			$this->Session->setFlash(__d('<?php echo $admin->plugin; ?>', 'Invalid <?php echo ucfirst(strtolower($singularHumanName)); ?>', true), 'flash/error');
			$this->redirect(array('action' => '<?php echo $admin->redirectTo; ?>'));
<?php else: ?>
			$this->flash(__d('<?php echo $admin->plugin; ?>', 'Invalid <?php echo ucfirst(strtolower($singularHumanName)); ?>', true), array('action' => '<?php echo $admin->redirectTo; ?>'));
<?php endif; ?>
		}
		$this->set(compact('<?php echo $singularName . Inflector::pluralize(Inflector::humanize($alias)); ?>'));
	}