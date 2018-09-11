# Configuring SSO on CentOS/Apache #

Follow this steps to configure the SSO (Single Sign-on) between Windows authentication and Apache server with **auth_ntlm_winbind** module.

## Configuring the server name ##

In the file `/etc/hostname`, set the Fully Qualified Domain Name (FQDN):

```
WEB1.your.domain
```

## Synchronizing clock with Active Directory ##

Installing the ntp:

```
yum intall ntpdate ntp
```

Edit the file `/etc/ntp.conf`, leaving only the LDAPs servers. Example:

```
# LDAP server IP
server 192.168.1.1
```

Restart service:

```
systemctl restart ntpd
```

Synchronizing clock:

```
ntpdate -s 192.168.1.1
```

## Configuring Kerberos ##

Installing the packages:

```
yum install krb5-libs krb5-workstation pam_krb5
```

Edit the file `/etc/krb5.conf` leaving like this example:

```
[logging]
        default = FILE:/var/log/krb5.log
        kdc = FILE:/var/log/krb5kdc.log
        admin_server = FILE:/var/log/kadmind.log

[libdefaults]
        dns_lookup_realm = false
        ticket_lifetime = 24h
        renew_lifetime = 7d
        forwardable = true
        rdns = false
        default_realm = YOUR.DOMAIN
        default_ccache_name = KEYRING:persistent:%{uid}

[realms]
YOUR.DOMAIN = {
        kdc = 192.168.1.1
        }

[domain_realm]
 .your.domain = YOUR.DOMAIN
  your.domain = YOUR.DOMAIN
```

Testing the configuration:

```
kinit <AD User>
```

```
klist # Should show the token generated by the kinit command.
```

```
kdestroy
```

## Configuring Samba ##

Installing the packages:

```
yum install samba samba-winbind samba-winbind-clients oddjob-mkhomedir samba-winbind-krb5-locator
```

Edit the file `/etc/samba/smb.conf` according the example below (see the Samba documentation for more explanation):

```
[global]
	security = ADS
	realm= YOUR.DOMAIN 
	workgroup = DOMAIN 
	netbios name = WEB1
	server string = Server description (optional)

	idmap config * : range = 2000-9999
	idmap config * : backend = tdb

	idmap config DOMINIO : schema_mode = rfc2307
	idmap config DOMINIO : range = 100000-399999
	idmap config DOMINIO : default = yes
	idmap config DOMINIO : backend = rid

	winbind enum users = yes
	winbind enum groups = yes
	
	template homedir = /home/%D/%U
	template shell = /bin/bash 
	
	client use spnego = yes
	winbind use default domain = yes
	restrict anonymous = 2
	winbind refresh tickets = yes 
```

**Tip**: Use the command `testparm` to check the samba configuration.

Restart service:

```
systemctl restart smb
```

## Joining the server to Domain ##

```
net ads join -U domainAdminUser
```

## Configuring Apache ##

Install the **auth_ntlm_winbind** module following the instructions described in http://adldap.sourceforge.net/wiki/doku.php?id=mod_auth_ntlm_winbind.

**Note**: You can also download the mod_auth_ntlm_winbind.rpm from rpm repositories as https://www.rpmfind.net and install with `yum localinstall <file.rpm>`


Move the configuration file for the correct local:

```
mv /etc/httpd/conf.d/auth_ntlm_winbind.conf ../conf.modules.d
```

Edit the file `/etc/httpd/conf/httpd.conf`, set **keepAlive** *On* and add the authentication module to Wordpress directory.
If Wordpress is on `/var/www`, then:

```ApacheConf
keepAlive On

<Directory /var/www/>
    Options FollowSymLinks
    AllowOverride FileInfo
    AuthName "Intranet Access"
    NTLMAuth on
    NTLMAuthHelper "/usr/bin/ntlm_auth --domain=your.domain --helper-protocol=squid-2.5-ntlmssp"
    NTLMBasicAuthoritative on
    AuthType NTLM
    require valid-user
</Directory>
```

Restart Apache:

```
systemctl restart httpd
```

Finally, access the Wordpress Admin and enable the **SSO** in the `simple-LDAP-plugin` configuration.

## Configuring Firefox ##

On Firefox, you must add your domain as **trusted** if you want to use the SSO. In the
`about:config` change the key `network.automatic-ntlm-auth.trusted-uris`, adding your domain 
like `.seu.dominio`.

