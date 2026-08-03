# High Availability

## Overview

High Availability (HA) in LibreNMS keeps the system in continuous operation and decreases downtime. To do this, it adds redundancy for two critical components:

- **Polling**: The data collection process
- **WebUI**: The web interface for users

To get high availability, you must make sure that these components are redundant:

1. **Database**: MySQL/MariaDB with clustering
2. **Redis w/ Redis Sentinel**: For session management and caching
3. **RRD Files**: For storage of polled data

Also make sure that the **poller uses a distributed setup**, which must be the [LibreNMS Dispatcher service](../Extensions/Dispatcher-Service.md).

To keep the configuration simple, the web-ui and the poller can use the same Redis Sentinel cluster.

## Note about RRD Files

Pollers must write RRD data to files on a disk to keep polled data. We recommend
RRDCached, which receives RRD data through TCP/IP. This does not give HA for RRD
data, but it lets multiple pollers write to the same RRD files through a network connection.
This is described in [RRDCached.md](../Extensions/RRDCached.md).

One method to add HA support for RRD is shared storage through NFS with GlusterFS or a similar system.

## WebUI High Availability

The WebUI gets HA through multiple LibreNMS instances that share these backend services:

- Clustered Database
- Redis with Sentinel
- Centralized RRD Storage

### Implementation

1. **Configure Database HA**: 
   - Set up a Galera Cluster
   - Refer to [Galera-Cluster.md](../Extensions/Galera-Cluster.md) for the full instructions

2. **Configure Redis HA**:
   - Install Redis Sentinel
   - Refer to [Redis-Sentinel.md](../Extensions/Redis-Sentinel.md) for the configuration details

3. **Deploy multiple LibreNMS instances**:
   - Install LibreNMS on multiple servers
   - Configure each instance to use the same database and the same Redis Sentinel cluster
   - Make sure that the `.env` configurations are identical on all instances. Set `APP_KEY` to the same value on all instances.
   - Each installation must have a unique `NODE_ID` in your `.env`.

4. **Configure RRD Access**:
    Use RRDCached, which lets all instances get access to the same RRD files. Or use shared storage for the RRD files through NFS or a similar system.

## Polling High Availability

With distributed polling, multiple pollers operate together. This gives load distribution and failover capability.

!!! warning
    The poller does not support MySQL Galera clustering. Thus, you must use a TCP load balancer, such as Nginx or HAProxy,
    in front of the cluster to point to the cluster nodes.

### Implementation

1. **Configure distributed polling**:
   - Do the steps in [Distributed-Poller.md](../Extensions/Distributed-Poller.md)
   - Make sure that all pollers connect to the clustered database and Redis Sentinel, and can get access to the same RRD files.
