# MariaDB Galera Cluster

This is currently being tested, use at your own risk.

LibreNMS can be used with a MariaDB Galera Cluster. This is a Multi Master cluster, meaning each
node in the cluster can read and write to the database. They all have the same ability. LibreNMS will
randomly choose a working node to read and write requests to.


For more information see
<https://laravel.com/docs/database#read-and-write-connections>


## Getting Started

 * The best procedure is a minimum of 3 nodes in the cluster. An odd number of nodes is recommended. Then, when nodes do not agree on data, they have a tie breaker.
 * It's recommended that all servers be similar in hardware performance, cluster performance can be affected by the slowest server in the cluster.
 * Backup the database before starting, and backing up the database regularly is still recommended even in a working cluster environment.

## Install and Configure Galera

### Install Galera4 and MariaDB Server

These can be obtained from your OS package manager. For example in Ubuntu.
```bash
sudo apt-get install mariadb-server mariadb-client galera-4
```

### Create Galera Config

Create a new file /etc/mysql/conf.d/galera.conf on each node

```galera.conf
[mysqld]
binlog_format=ROW
default-storage-engine=innodb
innodb_autoinc_lock_mode=2
bind-address=0.0.0.0

# Galera Provider Configuration
wsrep_on=ON
wsrep_provider=/usr/lib/galera/libgalera_smm.so

# Galera Cluster Configuration
wsrep_cluster_name="librenms_cluster"
wsrep_cluster_address="gcomm://192.168.1.35,192.168.1.36,192.168.1.37,192.168.1.38,192.168.1.39"

# Galera Synchronization Configuration
wsrep_sst_method=rsync

# Galera Node Configuration
wsrep_node_address="192.168.1.35"
wsrep_node_name="librenms1.35"
```
Change the following values for your environment.
* wsrep_cluster_address -  All the IP address's of your nodes.
* wsrep_cluster_name - Name of the cluster. It must be the same for all nodes
* wsrep_node_address - IP address of this node.
* wsrep_node_name - Name of this node.

### Edit LibreNMS .env

LibreNMS supports up to 9 galera nodes, you define these nodes in the .env file. For each node we have the ability to define if this librenms installation/poller is able to write, read or both to that node.
The galera nodes that you define here can be the same, or different, for each librenms poller. If you have a poller that must write/read to only one galera node, add one DB_HOST and no others. With this, you have accurate control of the galera nodes from which a librenms poller reads, and to which it writes.

* DB_HOST is always set to read/write.
* DB_HOST must be set, however, it does not have to be the same on each poller, it can be different as long as it's part of the same galera cluster.
* If the node that is set as DB_HOST is down, items such as the ```lnms db``` command do not operate. They use only DB_HOST and do not change to other nodes.
* Set DB_CONNECTION=mysql_cluster to enable
* DB_STICKY can be used if you are pulling out of sync data form the database in a read request. For more information see
<https://laravel.com/docs/database#the-sticky-option>

The below example setting up 5 nodes

```dotenv
DB_HOST=192.168.1.35
DB_HOST_R2=192.168.1.36
DB_HOST_R3=192.168.1.37
DB_HOST_R4=192.168.1.38
DB_HOST_R5=192.168.1.39
DB_HOST_W2=192.168.1.36
DB_HOST_W3=192.168.1.37

DB_STICKY=true
DB_CONNECTION=mysql_cluster
DB_DATABASE=librenms
DB_USERNAME=librenms
DB_PASSWORD=password
```
The .env above, on a librenms installation/poller, speaks to each galera node as follows.

* 192.168.1.35 - Read/Write
* 192.168.1.36 - Read/Write
* 192.168.1.37 - Read/Write
* 192.168.1.38 - Read Only
* 192.168.1.39 - Read Only

### Starting Galera Cluster for the first time.

1) Shutdown MariaDB server on ALL nodes.
    ```bash
    sudo systemctl stop mariadb-server
    ```
2) On the server with your existing database or any mariadb server if you are starting without existing data, run the following command
    ```bash
    sudo galera_new_cluster
    ```
3) Start the rest of the nodes normally.
    ```bash
    sudo systemctl start mariadb-server
    ```

### Galera Cluster Status

To see some stats on how the Galera cluster is preforming run the following.

```bash
lnms db
```
In the database run following mysql query
```mysql
SHOW GLOBAL STATUS LIKE 'wsrep_%';
```

|    Variable Name                     |    Value                                                        |   Notes                                                 |
|    :----:                            |    :----:                                                       |    :----:                                               |
| -----------------------------------  | ----------------------------------------------------------------|---------------------------------------------------------|
| wsrep_cluster_size                   | 2                                                               | Current number of nodes in Cluster                      |
| wsrep_cluster_state_uuid             | e71582f3-cf14-11eb-bcf6-a23029e16405                            | Last Transaction UUID. It must be the same for each node |
| wsrep_connected                      | On                                                              | On = Connected with other nodes                         |
| wsrep_local_state_comment            | Synced                                                          | Synced with other nodes                                 |



### Restarting the Entire Cluster

In a cluster environment, make sure that ALL nodes are not offline at the same time. Failed nodes can recover without problems, if one node stays online.
If ALL nodes are offline, do the steps below to make sure that you start the cluster with the newest database. To do this, log in to each node and run the command below


```grastate.dat
sudo cat /var/lib/data/grastate.dat
```

```
# GALERA saved state
version: 2.1
uuid:    e71582f3-cf14-11eb-bcf6-a23029e16405
seqno:   -1
safe_to_bootstrap: 1
```

If the safe_to_bootstrap = 1, then Galera determined that this node has the most up-to-date database and can be safeley used to start the cluster.

Once you have found a node that can be used for starting the cluster, follow the steps in starting for the first time.
