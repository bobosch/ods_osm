Contribution
============

In order to ease contributions there is a preconfigured ddev environment.

System Requirements
-------------------

1) DDEV and Docker installed

For an installation guide for your platform see

* https://ddev.com/get-started/ and
* https://ddev.readthedocs.io/en/stable/

2) `git lfs` installed

`git lfs` is used to handle larger files, like DB Dump and fileadmin, for the initial install.

See here how to install and work with it:

https://github.com/git-lfs/git-lfs

Setup the Development Environment
---------------------------------

1) Clone the repo, as you do with any other project
2) Switch to the project root
3) Fire up ddev `ddev start`
4) Initialize ddev with default data `ddev initialize`

Now you are able to login with `admin` and `password` into the TYPO3 backend and use the provided data for testing.




