.. include:: /Includes.rst.txt

==================
Sudo Mode in TYPO3
==================

:Extension key:
   sudo_mode

:Package name:
   friendsoftypo3/sudo-mode

:Version:
   |release|

:Language:
   en

:Author:
   Oliver Hader & Contributors

:License:
   This document is published under the
   `Creative Commons BY 4.0 <https://creativecommons.org/licenses/by/4.0/>`__
   license.

:Rendered:
   |today|

----

This extension adds the *sudo mode* to TYPO3: Certain backend actions have to be
confirmed with a password to prevent unwanted changes. If the confirmation is
successful, the password request is deactivated for a certain period of time.

In TYPO3 v9 LTS the `rsaauth <https://extensions.typo3.org/extension/rsaauth>`__
extension is also supported.

----

**Table of Contents:**

.. contents::
   :backlinks: top
   :depth: 2
   :local:

Installation
============

The latest version can be installed via `TER`_ or via composer by running

.. code-block:: bash

   composer require friendsoftypo3/sudo-mode

in a TYPO3 installation.

.. _TER: https://extensions.typo3.org/extension/sudo_mode

What does it do?
================

.. figure:: /Images/PasswordConfirmationDialog.png
   :alt: Password Confirmation Dialog

   Password Confirmation Dialog

When editing entities that are essential for a TYPO3 website - such as backend
users (`be_users`), backend user groups (`be_groups`) or file storages
(`sys_file_storage`) - this extension ensures that changes are really intended
before actually persisting to the database.

Currently this extension intercepts modification commands for the central
persistence component `DataHandler`, direct invocation of the database
connection is not covered, yet. Changes to configured database tables have to
be confirmed with the password of the current user. These confirmations are
cached for a given amount of time (5 minutes / 300 seconds) - once these
confirmations have expired future modifications have to confirmed again.

Configuration
=============

.. figure:: /Images/ExtensionConfiguration.png
   :alt: Extension Configuration Settings in Admin Tools

   Extension Configuration Settings in Admin Tools

.. confval:: expiration

   :type: int
   :default: ``300``

   Confirmation Expiration: Remember password confirmation for this amount of
   seconds.

.. confval:: tableNames

   :type: string
   :default: ``be_users,be_groups``

   Table Names: Database table names that require confirmation prior to be
   modified (`be_users` and `be_groups` are always processed and cannot be
   disabled).

Logging
=======

In order to log sudo mode events `LOG` section in `LocalConfiguration.php` has
to be extended like shown in the following example (writer configuration and
file names can be adjusted of course):

Logging configuration
---------------------

.. code-block:: php

   'LOG' => [
       // ...
       'FriendsOfTYPO3' => [
           'SudoMode' => [
               'writerConfiguration' => [
                   \TYPO3\CMS\Core\Log\LogLevel::INFO => [
                       \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                           'logFileInfix' => 'sudo_mode'
                       ],
                   ],
               ],
           ],
       ],
       // ...
   ],

Log file extract (example)
--------------------------

.. code-block:: none

   Mon, 30 Mar 2020 13:55:25 +0200 [WARNING] request="2523fea6f60fe" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationController": Password verification failed - {"id":"d26dcc84d8f817481700","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 13:55:30 +0200 [INFO] request="2af7ee66cf30a" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationController": Password verification succeeded - {"id":"d26dcc84d8f817481700","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 15:22:42 +0200 [NOTICE] request="a55cd939a1d05" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationHandler": Password verification purged - {"id":"55c9fea912d488cfaed9","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 15:22:42 +0200 [NOTICE] request="a55cd939a1d05" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationHandler": Password verification purged - {"id":"d26dcc84d8f817481700","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 15:22:42 +0200 [INFO] request="a55cd939a1d05" component="FriendsOfTYPO3.SudoMode.Middleware.RequestHandlerGuard": Handled verification request - {"route":"/record/edit","id":"3985d0ea9762073e6776","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 15:22:47 +0200 [INFO] request="96599fbb566ae" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationController": Password verification succeeded - {"id":"3985d0ea9762073e6776","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 15:23:17 +0200 [INFO] request="d3de2121ef4c2" component="FriendsOfTYPO3.SudoMode.Middleware.RequestHandlerGuard": Handled verification request - {"route":"/module/user/setup","id":"5d7cca16b7daa4a473e1","type":"tableName","subjects":["be_users"],"user":1}
   Mon, 30 Mar 2020 15:23:29 +0200 [INFO] request="e25b5ec356dad" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationController": Password verification succeeded - {"id":"5d7cca16b7daa4a473e1","type":"tableName","subjects":["be_users"],"user":1}
   Mon, 30 Mar 2020 18:06:10 +0200 [NOTICE] request="4066beb59e1c2" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationHandler": Password verification purged - {"id":"3985d0ea9762073e6776","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 18:06:10 +0200 [NOTICE] request="4066beb59e1c2" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationHandler": Password verification purged - {"id":"5d7cca16b7daa4a473e1","type":"tableName","subjects":["be_users"],"user":1}
   Mon, 30 Mar 2020 18:06:10 +0200 [INFO] request="4066beb59e1c2" component="FriendsOfTYPO3.SudoMode.Middleware.RequestHandlerGuard": Handled verification request - {"route":"/record/edit","id":"c313dae09718a6e3e520","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 18:06:21 +0200 [INFO] request="be5a5e61e4d77" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationController": Password verification succeeded - {"id":"c313dae09718a6e3e520","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 18:15:44 +0200 [NOTICE] request="078f18d21ae91" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationHandler": Password verification purged - {"id":"c313dae09718a6e3e520","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 18:15:44 +0200 [INFO] request="078f18d21ae91" component="FriendsOfTYPO3.SudoMode.Middleware.RequestHandlerGuard": Handled verification request - {"route":"/record/edit","id":"c3d718aea7373ec9f353","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 18:15:50 +0200 [INFO] request="9b9fe24104f46" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationController": Password verification succeeded - {"id":"c3d718aea7373ec9f353","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 22:00:49 +0200 [NOTICE] request="b71f437bbfa0f" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationHandler": Password verification purged - {"id":"c3d718aea7373ec9f353","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 22:11:21 +0200 [INFO] request="e872ce40b3a2c" component="FriendsOfTYPO3.SudoMode.Middleware.RequestHandlerGuard": Handled verification request - {"route":"/record/edit","id":"92c4d2dc5e0adeb20896","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 22:11:31 +0200 [WARNING] request="23d346749747d" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationController": Password verification failed - {"id":"92c4d2dc5e0adeb20896","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 22:11:37 +0200 [INFO] request="22ad9094c3428" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationController": Password verification succeeded - {"id":"92c4d2dc5e0adeb20896","type":"tableName","subjects":["be_groups"],"user":1}
   Mon, 30 Mar 2020 22:29:31 +0200 [INFO] request="d0426dc7606e6" component="FriendsOfTYPO3.SudoMode.Middleware.RequestHandlerGuard": Handled verification request - {"route":"/record/edit","id":"efdfeab9b5a424f85d39","type":"tableName","subjects":["be_users"],"user":1}
   Mon, 30 Mar 2020 22:30:10 +0200 [INFO] request="b0a893aba5b39" component="FriendsOfTYPO3.SudoMode.Backend.ConfirmationController": Password verification succeeded - {"id":"efdfeab9b5a424f85d39","type":"tableName","subjects":["be_users"],"user":1}
